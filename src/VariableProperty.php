<?php

declare(strict_types=1);

/*
 * This file is part of the Ruler package, an OpenSky project.
 *
 * (c) 2013 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace D6N\RuleEngine;

/**
 * A propositional VariableProperty.
 *
 * A VariableProperty is a special propositional Variable which maps to a
 * property, method or offset of another Variable. During evaluation, they are
 * replaced with terminal Values from properties of their parent Variable,
 * either from their default Value, or from the current Context.
 *
 * @author Justin Hileman <justin@justinhileman.info>
 */
class VariableProperty extends Variable implements PropertyReference
{
    /**
     * VariableProperty class constructor.
     *
     * @param Variable $parent Parent Variable instance
     * @param string   $name   Property name
     * @param mixed    $value  Default Property value (default: null)
     */
    public function __construct(private readonly Variable $parent, string $name, mixed $value = null)
    {
        parent::__construct($name, $value);
    }

    #[\Override]
    public function getParent(): Variable
    {
        return $this->parent;
    }

    /**
     * Prepare a Value for this VariableProperty given the current Context.
     *
     * To retrieve a Value, the parent Variable is first resolved given the
     * current context. Then, depending on its type, a method, property or
     * offset of the parent Value is returned.
     *
     * If the parent Value is an object, and this VariableProperty name is
     * "bar", it will do a prioritized lookup for:
     *
     *  1. A method named `bar`
     *  2. A public property named `bar`
     *  3. ArrayAccess + offsetExists named `bar`
     *
     * If it is an array:
     *
     *  1. Array index `bar`
     *
     * Otherwise, return the default value for this VariableProperty.
     *
     * @param Context $context The current Context
     */
    #[\Override]
    public function prepareValue(Context $context): Value
    {
        return PropertyResolver::resolve(
            $this->parent->prepareValue($context)->getValue(),
            (string) $this->getName(),
            $this->getValue(),
        );
    }
}
