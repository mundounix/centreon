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
 */
namespace Controllers;

/**
 * Validators controller
 *
 * @authors Lionel Assepo
 * @package Centreon
 * @subpackage Controllers
 */
class ValidatorsController extends \Centreon\Core\Controller
{
    /**
     * 
     * @method post
     * @route /validator/email
     */
    public function emailAction()
    {
        $params = $this->getParams('post');
        
        if (filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
            echo "success";
        } else {
            echo "fail";
        }
    }
    
    /**
     * 
     * @method post
     * @route /validator/resolvedns
     */
    public function resolveDnsAction()
    {
        $params = $this->getParams('post');
        
        $ipAddress = gethostbyname($params['dnsname']);
        
        if (filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            echo $ipAddress;
        } else {
            echo "fail";
        }
    }
    
    /**
     * 
     * @method post
     * @route /validator/ipaddress
     */
    public function ipAddressAction()
    {
        $params = $this->getParams('post');
        
        if (filter_var($params['ipaddress'], FILTER_VALIDATE_IP)) {
            echo "success";
        } else {
            echo "fail";
        }
    }
    
    /**
     * 
     * @method post
     * @route /validator/unique
     */
    public function uniqueAction()
    {
        $params = $this->getParams('post');
        
        $callableObject = '\\Models\\Configuration\\Relation\\'.ucwords($params['object']);
        if ($callableObject::isUnique($params['value'])) {
            echo "unique";
        } else {
            echo "not unique";
        }
    }
}