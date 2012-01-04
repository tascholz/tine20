<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle containers in CardDAV tree
 *
 * @package     Filemanager
 * @subpackage  Frontend
 */
class Filemanager_Frontend_WebDAV_Container extends Tinebase_WebDav_Container_Abstract
{
    protected $_applicationName = 'Filemanager';
    
    protected $_model = 'File';
    
    protected $_suffix = null;

    /**
     * contructor
     * 
     * @param  string|Tinebase_Model_Application  $_application  the current application
     * @param  string                             $_container    the current path
     */
    public function __construct(Tinebase_Model_Container $_container, $_useIdAsName = false)
    {
        parent::__construct($_container, $_useIdAsName);
        
        $this->_fileSystemPath = '/' . $this->_application->getId() . '/folders/' . $this->_container->type . '/';
        
        if ($this->_container->type == Tinebase_Model_Container::TYPE_SHARED) {
            $this->_fileSystemPath .= $this->_container->getId();
        } else {
            $this->_fileSystemPath .= Tinebase_Core::getUser()->accountId . '/' . $this->_container->getId();
        }
    }
     
    public function getChild($name)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' path: ' . $this->_fileSystemPath . '/' . $name);
    
        if ($name[0]=='.')  {
            throw new Sabre_DAV_Exception_FileNotFound('Access denied');
        }
        
        try {
            $childNode = Tinebase_FileSystem::getInstance()->stat($this->_fileSystemPath . '/' . $name);
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new Sabre_DAV_Exception_FileNotFound('file not found: ' . $this->_fileSystemPath . '/' . $name);
        }
        
        if ($childNode->type == Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
            return new Filemanager_Frontend_WebDAV_Directory($this->_fileSystemPath . '/' . $name);
        } else {
            return new Filemanager_Frontend_WebDAV_File($this->_fileSystemPath . '/' . $name);
        }
    }
    
    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre_DAV_INode[]
     */
    function getChildren()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' path: ' . $this->_fileSystemPath);
        
        $children = array();
            
        // Loop through the directory, and create objects for each node
        foreach(Tinebase_FileSystem::getInstance()->scanDir($this->_fileSystemPath) as $node) {
            $children[] = $this->getChild($node->name);
        }
        
        return $children;
    }
    
    /**
     * Returns the list of properties
     *
     * @param array $requestedProperties
     * @return array
     */
    public function getProperties($requestedProperties) 
    {
        $displayName = $this->_container->name;
        
        $properties = array(
#            '{http://calendarserver.org/ns/}getctag' => round(time()/60),
            'id'                                     => $this->_container->getId(),
            'uri'                                    => $this->_useIdAsName == true ? $this->_container->getId() : $this->_container->name,
#            '{DAV:}resource-id'                      => 'urn:uuid:' . $this->_container->getId(),
#            '{DAV:}owner'                            => new Sabre_DAVACL_Property_Principal(Sabre_DAVACL_Property_Principal::HREF, 'principals/users/' . Tinebase_Core::getUser()->contact_id),
        	'{DAV:}displayname'                      => $displayName,
        );
        
        $response = array();
    
        foreach($requestedProperties as $prop) {
            if (isset($properties[$prop])) {
                $response[$prop] = $properties[$prop];
            }
        }
        
        return $response;
    }
}