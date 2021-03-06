<?php
/*
 * Copyright 2005-2014 MERETHIS
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give MERETHIS
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of MERETHIS choice, provided that
 * MERETHIS also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 *
 */

namespace CentreonRealtime\Models;

use Centreon\Internal\Di;

/**
 * Centreon Object Relation
 *
 * @author sylvestre
 */
abstract class Relation
{
    /**
     * Relation Table
     */
    protected static $relationTable = null;

    /**
     * First key
     */
    protected static $firstKey = null;

    /**
     * Second key
     */
    protected static $secondKey = null;

    /**
     * @var string
     */
    public static $firstObject = null;

    /**
     *
     * @var string
     */
    public static $secondObject = null;

    /**
     * Used for inserting relation into database
     *
     * @param int $fkey
     * @param int $skey
     * @return void
     */
    public static function insert($fkey, $skey = null)
    {
        $sql = "INSERT INTO " . static::$relationTable . " ( " . static::$firstKey . ", " . static::$secondKey . ") 
            VALUES (?, ?)";
        $db = Di::getDefault()->get('db_centreon');
        $stmt = $db->prepare($sql);
        $stmt->execute(array($fkey, $skey));
    }

    /**
     * Used for deleting relation from database
     *
     * @param int $fkey
     * @param int $skey
     * @return void
     */
    public static function delete($fkey, $skey = null)
    {
        if (isset($fkey) && isset($skey)) {
            $sql = "DELETE FROM " . static::$relationTable .
                "WHERE " . static::$firstKey . " = ? AND " . static::$secondKey . " = ?";
            $args = array($fkey, $skey);
        } elseif (isset($skey)) {
            $sql = "DELETE FROM " . static::$relationTable . " WHERE ". static::$secondKey . " = ?";
            $args = array($skey);
        } else {
            $sql = "DELETE FROM " . static::$relationTable . " WHERE " . static::$firstKey . " = ?";
            $args = array($fkey);
        }
        $db = Di::getDefault()->get('db_centreon');
        $stmt = $db->prepare($sql);
        $stmt->execute($args);
    }

    /**
     * Get result
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    protected static function getResult($sql, $params = array())
    {
        $db = Di::getDefault()->get('db_centreon');
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * Get Merged Parameters from seperate tables
     *
     * @param array $firstTableParams
     * @param array $secondTableParams
     * @param int $count
     * @param string $order
     * @param string $sort
     * @param array $filters
     * @param string $filterType
     * @param array $relationTableParams
     * @return array
     */
    public static function getMergedParameters(
        $firstTableParams = array(),
        $secondTableParams = array(),
        $count = -1,
        $offset = 0,
        $order = null,
        $sort = "ASC",
        $filters = array(),
        $filterType = "OR",
        $relationTableParams = array()
    ) {
        $fString = "";
        $sString = "";
        $rString = "";
        $firstObj = static::$firstObject;
        foreach ($firstTableParams as $fparams) {
            if ($fString != "") {
                $fString .= ",";
            }
            $fString .= $firstObj::getTableName().".".$fparams;
        }
        $secondObj = static::$secondObject;
        foreach ($secondTableParams as $sparams) {
            if ($fString != "" || $sString != "") {
                $sString .= ",";
            }
            $sString .= $secondObj::getTableName().".".$sparams;
        }
        foreach ($relationTableParams as $rparams) {
            if ($fString != "" || $sString != "" || $rString != "") {
                $rString .= ",";
            }
            $rString .= static::$relationTable.".".$rparams;
        }
        $sql = "SELECT $fString $sString $rString
        		FROM ". $firstObj::getTableName().",".$secondObj::getTableName().",". static::$relationTable."
        		WHERE ".$firstObj::getTableName().".".$firstObj::getPrimaryKey()
                    ." = ".static::$relationTable.".".static::$firstKey."
        		AND ".static::$relationTable.".".static::$secondKey
                    ." = ".$secondObj::getTableName().".".$secondObj::getPrimaryKey();
        $filterTab = array();
        if (count($filters)) {
            foreach ($filters as $key => $rawvalue) {
                $sql .= " $filterType $key LIKE ? ";
                $value = trim($rawvalue);
                $value = str_replace("\\", "\\\\", $value);
                $value = str_replace("_", "\_", $value);
                $value = str_replace(" ", "\ ", $value);
                $filterTab[] = $value;
            }
        }
        if (isset($order) && isset($sort) && (strtoupper($sort) == "ASC" || strtoupper($sort) == "DESC")) {
            $sql .= " ORDER BY $order $sort ";
        }
        if (isset($count) && $count != -1) {
            $db = Di::getDefault()->get('db_centreon');
            $sql = $db->limit($sql, $count, $offset);
        }
        $result = static::getResult($sql, $filterTab);
        return $result;
    }


    /**
     * Get target id from source id
     *
     * @param int $sourceKey
     * @param int $targetKey
     * @param array $sourceId
     * @return array
     */
    public static function getTargetIdFromSourceId($targetKey, $sourceKey, $sourceId)
    {
        if (!is_array($sourceId)) {
            $sourceId = array($sourceId);
        }
        $sql = "SELECT $targetKey FROM " . static::$relationTable . " WHERE $sourceKey = ?";
        $result = static::getResult($sql, $sourceId);
        $tab = array();
        foreach ($result as $rez) {
            $tab[] = $rez[$targetKey];
        }
        return $tab;
    }

    /**
     * Generic method that allows to retrieve target ids
     * from another another source id
     *
     * @param string $name
     * @param array $args
     * @return array
     * @throws Exception
     */
    public function __call($name, $args = array())
    {
        if (!count($args)) {
            throw new Exception('Missing arguments');
        }
        if (!isset(static::$secondKey)) {
            throw new Exception("Not a relation table");
        }
        if (preg_match('/^get([a-zA-Z0-9_]+)From([a-zA-Z0-9_]+)/', $name, $matches)) {
            if (($matches[1] != static::$firstKey && $matches[1] != static::$secondKey) ||
                ($matches[2] != static::$firstKey && $matches[2] != static::$secondKey)) {
                throw new Exception('Unknown field');
            }
            return static::getTargetIdFromSourceId($matches[1], $matches[2], $args);
        } elseif (preg_match('/^delete_([a-zA-Z0-9_]+)/', $name, $matches)) {
            if ($matches[1] == static::$firstKey) {
                static::delete($args[0]);
            } elseif ($matches[1] == static::$secondKey) {
                static::delete(null, $args[0]);
            } else {
                throw new Exception('Unknown field');
            }
        } else {
            throw new Exception('Unknown method');
        }
    }

    /**
     * Get First Key
     *
     * @return string
     */
    public static function getFirstKey()
    {
        return static::$firstKey;
    }

    /**
     * Get Second Key
     *
     * @return string
     */
    public static function getSecondKey()
    {
        return static::$secondKey;
    }
}
