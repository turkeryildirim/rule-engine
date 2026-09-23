<?php

declare(strict_types=1);

namespace D6N\RuleEngine;

use D6N\RuleEngine\Exception\SerializationException;
use D6N\RuleEngine\Exception\UnknownOperatorException;
use D6N\RuleEngine\Operator\CountingOperator;
use D6N\RuleEngine\Operator\LogicalOperator;

/**
 * Exports Rules and RuleSets to JSON (or plain arrays) and imports them back.
 *
 * Conditions are stored as a tree of nodes:
 *
 *   {"op": "greaterThan", "operands": [...]}          an operator; "count" for atLeast/atMost/exactly
 *   {"var": "orderTotal"}                             a fact, optionally with a "default"
 *   {"property": "roles", "of": {...}}                a property of another node, optionally with a "default"
 *   {"value": 50}                                     a literal
 *   {"rule": {"name": ..., "condition": {...}}}       a Rule nested inside a condition
 *
 * Actions are closures and cannot be stored; pass them to the import methods
 * keyed by rule name. Operators are stored by their OperatorRegistry name, so
 * custom operators must be registered (or their namespace must be) on both ends.
 */
final class RuleSerializer
{
    public const int VERSION = 1;

    private const int JSON_FLAGS = \JSON_THROW_ON_ERROR | \JSON_PRESERVE_ZERO_FRACTION | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE;

    private readonly OperatorRegistry $operators;

    public function __construct(?OperatorRegistry $operators = null)
    {
        $this->operators = $operators ?? new OperatorRegistry();
    }

    /**
     * @throws SerializationException if the rule cannot be represented as JSON
     */
    public function toJson(Rule|RuleSet $rules, bool $pretty = true): string
    {
        return \json_encode($this->toArray($rules), self::JSON_FLAGS | ($pretty ? \JSON_PRETTY_PRINT : 0));
    }

    /**
     * @return array<string, mixed>
     *
     * @throws SerializationException if the rule cannot be represented as JSON
     */
    public function toArray(Rule|RuleSet $rules): array
    {
        if ($rules instanceof Rule) {
            return ['version' => self::VERSION, 'rule' => $this->exportRule($rules, 'rule')];
        }

        $exported = [];
        foreach ($rules->getRules() as $i => $rule) {
            $exported[] = $this->exportRule($rule, "rules[$i]", $rules->getPriority($rule) ?? 0);
        }

        return ['version' => self::VERSION, 'rules' => $exported];
    }

    /**
     * @param array<string, callable> $actions actions keyed by rule name
     *
     * @throws SerializationException if the document is not a valid rule
     */
    public function ruleFromJson(string $json, array $actions = []): Rule
    {
        return $this->ruleFromArray(self::decode($json), $actions);
    }

    /**
     * @param array<string, callable> $actions actions keyed by rule name
     *
     * @throws SerializationException if the document is not a valid rule set
     */
    public function ruleSetFromJson(string $json, array $actions = []): RuleSet
    {
        return $this->ruleSetFromArray(self::decode($json), $actions);
    }

    /**
     * @param array<mixed>            $document
     * @param array<string, callable> $actions  actions keyed by rule name
     *
     * @throws SerializationException if the document is not a valid rule
     */
    public function ruleFromArray(array $document, array $actions = []): Rule
    {
        self::checkVersion($document);

        return $this->importRule(self::field($document, 'rule', ''), 'rule', $actions);
    }

    /**
     * @param array<mixed>            $document
     * @param array<string, callable> $actions  actions keyed by rule name
     *
     * @throws SerializationException if the document is not a valid rule set
     */
    public function ruleSetFromArray(array $document, array $actions = []): RuleSet
    {
        self::checkVersion($document);
        $rules = self::field($document, 'rules', '');
        if (!\is_array($rules) || !\array_is_list($rules)) {
            throw new SerializationException('"rules" must be a list', 'rules');
        }

        $set = new RuleSet();
        foreach ($rules as $i => $item) {
            $path = "rules[$i]";
            $set->addRule($this->importRule($item, $path, $actions), self::priority($item, $path));
        }

        return $set;
    }

    /**
     * @return array{name?: string, priority?: int, condition: array<string, mixed>}
     */
    private function exportRule(Rule $rule, string $path, int $priority = 0): array
    {
        $exported = [];
        if (null !== $rule->getName()) {
            $exported['name'] = $rule->getName();
        }
        if (0 !== $priority) {
            $exported['priority'] = $priority;
        }
        $exported['condition'] = $this->exportNode($rule->getCondition(), $path.'.condition');

        return $exported;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws SerializationException if the node cannot be represented as JSON
     */
    private function exportNode(Proposition|VariableOperand $node, string $path): array
    {
        if ($node instanceof Rule) {
            return ['rule' => $this->exportRule($node, $path.'.rule')];
        }

        if ($node instanceof PropertyReference) {
            return ['property' => $node->getName(), 'of' => $this->exportNode($node->getParent(), $path.'.of')]
                + self::exportDefault($node->getValue(), $path);
        }

        if ($node instanceof Variable) {
            $value = $node->getValue();
            if (null !== $node->getName()) {
                return ['var' => $node->getName()] + self::exportDefault($value, $path);
            }

            return $value instanceof VariableOperand
                ? $this->exportNode($value, $path)
                : ['value' => self::literal($value, $path.'.value')];
        }

        try {
            $name = $this->operators->nameOf($node);
        } catch (UnknownOperatorException $e) {
            throw new SerializationException($e->getMessage(), $path, $e);
        }

        $exported = ['op' => $name];
        if ($node instanceof CountingOperator) {
            $exported['count'] = $node->getCount();
        }
        if ($node instanceof Operator) {
            $exported['operands'] = [];
            foreach ($node->getOperands() as $i => $operand) {
                $exported['operands'][] = $this->exportNode($operand, "$path.operands[$i]");
            }
        }

        return $exported;
    }

    /**
     * @return array{default?: mixed}
     */
    private static function exportDefault(mixed $default, string $path): array
    {
        return null === $default ? [] : ['default' => self::literal($default, $path.'.default')];
    }

    /**
     * @throws SerializationException if the value is not null, a scalar (finite, for floats) or an array of those
     */
    private static function literal(mixed $value, string $path): mixed
    {
        if (\is_array($value)) {
            foreach ($value as $key => $item) {
                self::literal($item, "{$path}[$key]");
            }

            return $value;
        }

        if (null === $value || (\is_scalar($value) && !(\is_float($value) && !\is_finite($value)))) {
            return $value;
        }

        throw new SerializationException(\sprintf('Cannot export %s; only null, scalars, finite floats and arrays can be stored', \is_float($value) ? 'a non-finite float' : 'a '.\get_debug_type($value).' value'), $path);
    }

    /**
     * @param array<string, callable> $actions
     *
     * @throws SerializationException if the node is not a valid rule
     */
    private function importRule(mixed $data, string $path, array $actions): Rule
    {
        if (!\is_array($data)) {
            throw new SerializationException('A rule must be an object', $path);
        }

        $name = $data['name'] ?? null;
        if (null !== $name && !\is_string($name)) {
            throw new SerializationException('"name" must be a string', $path.'.name');
        }

        $condition = $this->importNode(self::field($data, 'condition', $path), $path.'.condition');
        if (!$condition instanceof Proposition) {
            throw new SerializationException('A condition must be a proposition, not a value', $path.'.condition');
        }

        return new Rule($condition, null === $name ? null : ($actions[$name] ?? null), $name);
    }

    /**
     * @throws SerializationException if the node is not valid
     */
    private function importNode(mixed $data, string $path): Proposition|VariableOperand
    {
        if (!\is_array($data)) {
            throw new SerializationException('A node must be an object', $path);
        }

        return match (true) {
            \array_key_exists('op', $data)       => $this->importOperator($data, $path),
            \array_key_exists('var', $data)      => new Variable(self::string($data, 'var', $path), $data['default'] ?? null),
            \array_key_exists('property', $data) => $this->importProperty($data, $path),
            \array_key_exists('value', $data)    => new Variable(null, $data['value']),
            \array_key_exists('rule', $data)     => $this->importRule($data['rule'], $path.'.rule', []),
            default                              => throw new SerializationException('Unknown node; expected one of "op", "var", "property", "value" or "rule"', $path),
        };
    }

    /**
     * @param array<mixed> $data
     *
     * @throws SerializationException if the property node is not valid
     */
    private function importProperty(array $data, string $path): VariableProperty
    {
        $of = self::field($data, 'of', $path);
        $parent = match (true) {
            \is_array($of) && \array_key_exists('var', $of)      => new Variable(self::string($of, 'var', $path.'.of'), $of['default'] ?? null),
            \is_array($of) && \array_key_exists('property', $of) => $this->importProperty($of, $path.'.of'),
            default                                              => throw new SerializationException('A property can only be read from a "var" or "property" node', $path.'.of'),
        };

        return new VariableProperty($parent, self::string($data, 'property', $path), $data['default'] ?? null);
    }

    /**
     * @param array<mixed> $data
     *
     * @throws SerializationException if the operator node is not valid
     */
    private function importOperator(array $data, string $path): Proposition|VariableOperand
    {
        $name = self::string($data, 'op', $path);
        try {
            $class = $this->operators->resolve($name);
        } catch (UnknownOperatorException $e) {
            throw new SerializationException($e->getMessage(), $path.'.op', $e);
        }

        $operandData = $data['operands'] ?? [];
        if (!\is_array($operandData) || !\array_is_list($operandData)) {
            throw new SerializationException('"operands" must be a list', $path.'.operands');
        }
        $operands = [];
        foreach ($operandData as $i => $operand) {
            $operands[] = $this->importNode($operand, "$path.operands[$i]");
        }

        try {
            return match (true) {
                \is_subclass_of($class, CountingOperator::class) => new $class(self::count($data, $path), $operands),
                \is_subclass_of($class, LogicalOperator::class)  => new $class($operands),
                default                                          => new $class(...$operands),
            };
        } catch (\TypeError|\LogicException $e) {
            throw new SerializationException(\sprintf('Invalid operands for "%s": %s', $name, $e->getMessage()), $path, $e);
        }
    }

    /**
     * @return array<mixed>
     *
     * @throws SerializationException if the string is not a JSON object
     */
    private static function decode(string $json): array
    {
        try {
            $document = \json_decode($json, true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new SerializationException('Invalid JSON: '.$e->getMessage(), '', $e);
        }

        if (!\is_array($document)) {
            throw new SerializationException('The document must be a JSON object');
        }

        return $document;
    }

    /**
     * @param array<mixed> $document
     *
     * @throws SerializationException if the version is missing or unsupported
     */
    private static function checkVersion(array $document): void
    {
        if (self::VERSION !== ($document['version'] ?? null)) {
            throw new SerializationException(\sprintf('Unsupported document version; expected %d', self::VERSION), 'version');
        }
    }

    /**
     * @param array<mixed> $data
     *
     * @throws SerializationException if the key is missing
     */
    private static function field(array $data, string $key, string $path): mixed
    {
        if (!\array_key_exists($key, $data)) {
            throw new SerializationException(\sprintf('Missing "%s"', $key), $path);
        }

        return $data[$key];
    }

    /**
     * @param array<mixed> $data
     *
     * @throws SerializationException if the key is missing or not a string
     */
    private static function string(array $data, string $key, string $path): string
    {
        $value = self::field($data, $key, $path);
        if (!\is_string($value)) {
            throw new SerializationException(\sprintf('"%s" must be a string', $key), "$path.$key");
        }

        return $value;
    }

    /**
     * @param array<mixed> $data
     *
     * @throws SerializationException if the count is missing or not an integer
     */
    private static function count(array $data, string $path): int
    {
        $count = self::field($data, 'count', $path);
        if (!\is_int($count)) {
            throw new SerializationException('"count" must be an integer', $path.'.count');
        }

        return $count;
    }

    /**
     * @throws SerializationException if the priority is not an integer
     */
    private static function priority(mixed $item, string $path): int
    {
        $priority = \is_array($item) ? ($item['priority'] ?? 0) : 0;
        if (!\is_int($priority)) {
            throw new SerializationException('"priority" must be an integer', $path.'.priority');
        }

        return $priority;
    }
}
