<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\UtilsBundle\Classes;

use Contao\System;

class ClassUtil
{
    /**
     * @param string $class
     * @param array  $parents
     *
     * @return array
     */
    public function getParentClasses(string $class, array $parents = [])
    {
        $strParent = get_parent_class($class);
        if ($strParent) {
            $parents[] = $strParent;

            $parents = $this->getParentClasses($strParent, $parents);
        }

        return $parents;
    }

    /**
     * Filter class constants by given prefixes and return the extracted constants.
     *
     * @param string $class            the class that should be searched for constants in
     * @param array  $prefixes         an array of prefixes that should be used to filter the class constants
     * @param bool   $returnValueAsKey boolean Return the extracted array keys from its value, if true
     *
     * @return array the extracted constants as array
     */
    public function getConstantsByPrefixes(string $class, array $prefixes = [], bool $returnValueAsKey = true)
    {
        $arrExtract = [];

        if (!class_exists($class)) {
            return $arrExtract;
        }

        $objReflection = new \ReflectionClass($class);
        $arrConstants = $objReflection->getConstants();

        if (!is_array($arrConstants)) {
            return $arrExtract;
        }

        $arrExtract = System::getContainer()->get('huh.utils.array')->filterByPrefixes($arrConstants, $prefixes);

        return $returnValueAsKey ? array_combine($arrExtract, $arrExtract) : $arrExtract;
    }

    /**
     * Returns all classes in the given namespace.
     *
     * @param string $namespace
     *
     * @return array
     */
    public function getClassesInNamespace(string $namespace)
    {
        $arrOptions = [];

        foreach (get_declared_classes() as $strName) {
            if (System::getContainer()->get('huh.utils.string')->startsWith($strName, $namespace)) {
                $arrOptions[$strName] = $strName;
            }
        }

        asort($arrOptions);

        return $arrOptions;
    }

    /**
     * Returns all children of a given class.
     *
     * @param string $strNamespace
     *
     * @return array
     */
    public function getChildClasses(string $qualifiedClassName)
    {
        $arrOptions = [];

        foreach (get_declared_classes() as $strName) {
            if (in_array($qualifiedClassName, $this->getParentClasses($strName), true)) {
                $arrOptions[$strName] = $strName;
            }
        }

        asort($arrOptions);

        return $arrOptions;
    }
}
