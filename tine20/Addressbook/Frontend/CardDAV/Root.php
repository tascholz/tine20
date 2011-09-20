<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * root of tree for the CardDAV frontend
 *
 * This class handles the root of the CardDAV tree
 *
 * @package     Addressbook
 * @subpackage  Frontend
 */
class Addressbook_Frontend_CardDAV_Root extends Sabre_DAV_SimpleCollection
{
    public function __construct()
    {
        parent::__construct('root', array(
            new Sabre_DAV_SimpleCollection(Sabre_CardDAV_Plugin::ADDRESSBOOK_ROOT, array(
                new Addressbook_Frontend_CardDAV_Collection_Personal('Addressbook'),
            )),
            new Sabre_DAV_SimpleCollection(Sabre_CalDAV_Plugin::CALENDAR_ROOT, array(
                new Calendar_Frontend_CalDAV_Collection(),
            )),
            new Sabre_DAV_SimpleCollection('principals', array(
                new Sabre_DAVACL_PrincipalCollection(new Tinebase_WebDav_Principals(), 'principals/users'),
                new Sabre_DAVACL_PrincipalCollection(new Tinebase_WebDav_Principals(), 'principals/groups')
            )),
            
        ));
    }
}
