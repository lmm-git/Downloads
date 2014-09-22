<?php

/**
 * Downloads
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 */

/**
 * Class to control User interface
 */
class Downloads_Api_User extends Zikula_AbstractApi
{
    /**
     * Download Item status
     */

    const STATUS_ALL = -1;
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    /**
     * get download control objects
     * @return array of objects
     */
    public function getlinks()
    {
        $links = array ();

        if (SecurityUtil::checkPermission('Downloads::', '::', ACCESS_ADD)) {
            if(FormUtil::getPassedValue('category', null, 'GET') != null) {
                $url = ModUtil::url('Downloads', 'admin', 'edit', array('category' => FormUtil::getPassedValue('category', null, 'GET')));
            } else {
                $url = ModUtil::url('Downloads', 'admin', 'edit');
            }
            $links[] = array(
                'url' => $url,
                'text' => $this->__('New download'),
                'class' => 'z-icon-es-new');
        }

        if (SecurityUtil::checkPermission('Downloads::ManageCategories', '::', ACCESS_DELETE)) {
            $links[] = array(
                'url' => ModUtil::url('Downloads', 'admin', 'categoryList'),
                'text' => $this->__('Categories'),
                'class' => 'z-icon-es-cubes',
                'links' => array(
                    array('url' => ModUtil::url('Downloads', 'admin', 'categoryList'),
                        'text' => $this->__('View/edit Categories')),
                    array('url' => ModUtil::url('Downloads', 'admin', 'editCategory'),
                        'text' => $this->__('New category')),
                ));
        }
		return $links;
    }
    
    /**
     * get downloads filtered as requested
     * @param type $args
     * @return array of objects
     */
    public function getall($args)
    {
        // declare args
        $category = isset($args['category']) ? $args['category'] : 0;
        $startnum = isset($args['startnum']) ? $args['startnum'] : 0;
        $orderby = isset($args['orderby']) ? $args['orderby'] : 'title';
        $orderdir = isset($args['orderdir']) ? $args['orderdir'] : 'ASC';
        $limit = isset($args['limit']) ? $args['limit'] : $this->getVar('perpage');
        $status = isset($args['status']) ? $args['status'] : self::STATUS_ACTIVE;

        $downloads = $this->entityManager->getRepository('Downloads_Entity_Download')
                ->getCollection($orderby, $orderdir, $startnum, $category, $status, $limit);

        $result = array();
        foreach ($downloads as $key => $download) {
            if (((!SecurityUtil::checkPermission('Downloads::Item', $download->getLid() . '::', ACCESS_READ)) ||
                    (!SecurityUtil::checkPermission('Downloads::Category', $download->getCategory()->getCid() . '::', ACCESS_READ))) && $this->getVar('permissionhandling') == 0 ) {
                continue;
            } else {
                $result[$key] = $download;
            }
            if($this->getVar('permissionhandling') == 0) {
                if ((!SecurityUtil::checkPermission('Downloads::Item', $download->getLid() . '::', ACCESS_READ)) ||
                        (!SecurityUtil::checkPermission('Downloads::Category', $download->getCategory()->getCid() . '::', ACCESS_READ))) {
                    continue;
                } else {
                    $result[$key] = $download;
                }
            } else {
                if(!ModUtil::apiFunc($this->name, 'user', 'checkPermissions', array('category' => $download->getCategory()->getCid()))) {
                    continue;
                } else {
                    $result[$key] = $download;
                }
            }
        }
        return $result;
    }

    /**
     * count the number of results in the query
     * @param array $args
     * @return integer
     */
    public function countQuery($args)
    {
        $args['limit'] = -1;
        $items = $this->getall($args);
        return count($items);
    }

    /**
     * check permissions
     * @param $args['category']: Actual category
     * @return boolean
     */
    public function checkPermissions($args)
    {
        //check if $args['category'] is set
        if(!is_numeric($args['category'])) {
            LogUtil::registerError('$args[\'category\'] not set!' . $args['category']);
            return false;
        }
        //check if $args['category'] is valid if $args['category] != 0 (because the main category does not exists as db-entry)
        if($args['category'] != 0) {
                $category = $this->entityManager->getRepository('Downloads_Entity_Categories')->find($args['category']);
                if(!($category instanceof Downloads_Entity_Categories)) {
                    LogUtil::registerError($this->__('Passed category is invalid! (Does not exist)'));
                    return false;
                }
        }
        
        //get permissionhandling var
        switch($this->getVar('permissionhandling')) {
            case '0':
                LogUtil::registerError('Independend (not category based) securities are not controlled by the checkPermission API!');
                return false;
                break;
            case '10':
                if($args['category'] == 0) {
                    return SecurityUtil::checkPermission('Downloads::', '::', ACCESS_READ);
                } else {
                    return SecurityUtil::checkPermission('Downloads::Category', $args['category'] . '::', ACCESS_READ);
                }
                break;
            case '20':
                if($args['selfcall'] != true) {
                    if($args['category'] == 0) {
                        return SecurityUtil::checkPermission('Downloads::', '::', ACCESS_READ);
                    } else {
                        if(SecurityUtil::checkPermission('Downloads::Category', $args['category'] . '::', ACCESS_READ)){
                            return true;
                        }
                    }
                }
                
                if($category->getPid() == 0) {
                    return SecurityUtil::checkPermission('Downloads::', '::', ACCESS_READ);
                } else {
                    if(SecurityUtil::checkPermission('Downloads::Category', $category->getPid() . '::', ACCESS_READ)){
                        return true;
                    }
                }
                self::checkPermissions(array('category' => $category->getPid(), 'selfcall' => true));
                break;
            case '21':
                if($args['selfcall'] != true) {
                    if($args['category'] == 0) {
                        return SecurityUtil::checkPermission('Downloads::', '::', ACCESS_READ);
                    } else {
                        if(!SecurityUtil::checkPermission('Downloads::Category', $args['category'] . '::', ACCESS_READ)){
                            return false;
                        }
                    }
                }
               
                if($category->getPid() == 0) {
                    return SecurityUtil::checkPermission('Downloads::', '::', ACCESS_READ);
                } else {
                    if(!SecurityUtil::checkPermission('Downloads::Category', $category->getPid() . '::', ACCESS_READ)){
                        return false;
                    }
                }
                self::checkPermissions(array('category' => $category->getPid(), 'selfcall' => true));
                break;
            case '22':
                if($args['selfcall'] != true) {
                    if($args['category'] == 0) {
                        return SecurityUtil::checkPermission('Downloads::', '::', ACCESS_OVERVIEW);
                    } else {
                        if(!SecurityUtil::checkPermission('Downloads::Category', $args['category'] . '::', ACCESS_OVERVIEW)){
                            return false;
                        }
                        if(SecurityUtil::checkPermission('Downloads::Category', $args['category'] . '::', ACCESS_READ)){
                            return true;
                        }
                    }
                }
                if($category->getPid() == 0) {
                    return SecurityUtil::checkPermission('Downloads::', '::', ACCESS_READ);
                } else {
                    $parentcategory = $this->entityManager->getRepository('Downloads_Entity_Categories')->findOneBy(array('cid' => $category['pid']));
                    if(!SecurityUtil::checkPermission('Downloads::Category', $parentcategory->getCid() . '::', ACCESS_OVERVIEW)){
                        return false;
                    }
                    if(SecurityUtil::checkPermission('Downloads::Category', $parentcategory->getCid() . '::', ACCESS_READ)){
                        return true;
                    }
                }
                self::checkPermissions(array('category' => $parentcategory->getCid(), 'selfcall' => true));
                break;
        }
    }

    public function getSubCategories($args)
    {
        $category = isset($args['category']) ? $args['category'] : 0;

        $subcategories = $this->entityManager->getRepository('Downloads_Entity_Categories')->findBy(array('pid' => $category));

        foreach ($subcategories as $key => $subcategory) {
            // check module permissions
            if($this->getVar('permissionhandling') == 0) {
                if (!SecurityUtil::checkPermission('Downloads::Category', $subcategory->getCid() . '::', ACCESS_OVERVIEW)) {
                    unset($subcategories[$key]);
                }
            } else {
                if(!ModUtil::apiFunc($this->name, 'user', 'checkPermissions', array('category' => $subcategory->getCid()))) {
                    unset($subcategories[$key]);
                }
            }
        }

        return $subcategories;
    }

    /**
     * Clear cache for given item. Can be called from other modules to clear an item cache.
     *
     * @param $item - the item: array with data or id of the item
     */
    public function clearItemCache(Downloads_Entity_Download $item)
    {
        // Clear View_cache
        $cache_ids = array();
        $cache_ids[] = 'display|lid_' . $item->getLid();
        $cache_ids[] = 'view|cid_' . $item->getCid();
        $view = Zikula_View::getInstance('Downloads');
        foreach ($cache_ids as $cache_id) {
            $view->clear_cache(null, $cache_id);
        }

        // clear Theme_cache
        $cache_ids = array();
        $cache_ids[] = 'Downloads|user|display|lid_' . $item->getLid();
        $cache_ids[] = 'Downloads|user|view|category_' . $item->getCid(); // view function (item list by category)
        $cache_ids[] = 'homepage'; // for homepage (it can be adjustment in module settings)
        $theme = Zikula_View_Theme::getInstance();
        //if (Zikula_Core::VERSION_NUM > '1.3.2') {
        if (method_exists($theme, 'clear_cacheid_allthemes')) {
            $theme->clear_cacheid_allthemes($cache_ids);
        } else {
            // clear cache for current theme only
            foreach ($cache_ids as $cache_id) {
                $theme->clear_cache(null, $cache_id);
            }
        }
    }

}
