<?php

/**
 * Downloads
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 */
class Downloads_Form_Handler_Admin_Edit extends Zikula_Form_AbstractHandler
{

    /**
     * download id.
     *
     * When set this handler is in edit mode.
     *
     * @var integer
     */
    private $id;

    /**
     * Setup form.
     *
     * @param Zikula_Form_View $view Current Zikula_Form_View instance.
     *
     * @return boolean
     */
    public function initialize(Zikula_Form_View $view)
    {
        $id = FormUtil::getPassedValue('id', null, 'GET', FILTER_SANITIZE_NUMBER_INT);
        if ($id) {
            // load record with id
            $file = $this->entityManager->getRepository('Downloads_Entity_Download')->find($id);

            if ($file) {
                // switch to edit mode
                $this->id = $id;
                // assign current values to form fields
                $view->assign($file->toArray());
                $view->assign('category', $file->getCategory()->getCid());
            } else {
                return LogUtil::registerError($this->__f('File with id %s not found', $id));
            }
        } else {
            $this->view->assign('status', true);
            if(UserUtil::getVar('uid') != 0) {
                $this->view->assign('submitter', UserUtil::getVar('uname'));
            }
            $this->view->assign('version', 1);
        }
        
        if(!SecurityUtil::checkPermission('Downloads::', '::', ACCESS_ADMIN)) {
            $view->setStateData('returnurl', ModUtil::url('Downloads', 'user', 'main'));
        } else {
            $view->setStateData('returnurl', ModUtil::url('Downloads', 'admin', 'main'));
        }

        $view->assign('categories', Downloads_Util::getCatSelectArray(array()));

        return true;
    }

    /**
     * Handle form submission.
     *
     * @param Zikula_Form_View $view  Current Zikula_Form_View instance.
     * @param array     &$args Args.
     *
     * @return boolean
     */
    public function handleCommand(Zikula_Form_View $view, &$args)
    {
        $returnurl = $view->getStateData('returnurl');

        // process the cancel action
        if ($args['commandName'] == 'cancel') {
            return $view->redirect($returnurl);
        }

        $storage = $this->getVar('upload_folder');

        if ($args['commandName'] == 'delete') {
            if(SecurityUtil::checkPermission('Downloads::', '::', ACCESS_DELETE)) {
                $file = $this->entityManager->getRepository('Downloads_Entity_Download')->find($this->id);
                $oldname = $file->getUrl();
                $fullpath = DataUtil::formatForOS($oldname);
                @unlink($fullpath);
                $this->entityManager->remove($file);
                $this->entityManager->flush();
                ModUtil::apiFunc('Downloads', 'user', 'clearItemCache', $file);
                LogUtil::registerStatus($this->__f('Item [id# %s] deleted!', $this->id));
                return $view->redirect($returnurl);
            } else {
                $view->setPluginErrorMsg('title', $this->__('You are not authorized to delete this entry!'));
                return false;
            }
        }

        // check for valid form
        if (!$view->isValid()) {
            return false;
        }

        // load form values
        $data = $view->getValues();

        // validate that url or file is passed
        if (!$data['url'] && !$data['filename']['size']) {
            $plugin = $view->getPluginById('filename');
            $plugin->setError($this->__('You must upload a filename'));
            $plugin = $view->getPluginById('url');
            $plugin->setError($this->__('OR specify a download url.'));
            return false;
        }

        $newFileUploadedFlag = false;
        $data['update'] = new DateTime();
        $data['date'] = new DateTime();
        $data['status'] = (int)$data['status'];
        $data['category'] = $this->entityManager->getRepository('Downloads_Entity_Categories')->find($data['category']);

        if ((is_array($data['filename'])) && ($data['filename']['size'] > 0)) {
            $data['filesize'] = $data['filename']['size'];
            $filename = $this->getVar('upload_filename');
            $filename_array = explode('%', $filename);
            $filename = '';
            foreach($filename_array as $key => $item) {
                if($key%2 == 0) {
                    $filename .= $item;
                } else {
                    $item_array = explode(':', $item);
                    switch($item_array[0]) {
                        case 'title':
                            $filename .= $data['title'];
                            break;
                        case 'fileextension':
                            $finfo = finfo_open();
                            if(!$finfo) {
                                return LogUtil::registerError($this->__('Opening fileinfo database failed. Please check your server configuration!'));
                            }
                            $fileType = finfo_file($finfo, $data['filename']['tmp_name'], FILEINFO_MIME_TYPE);
                            $fileTypeArray = explode('/', $fileType);
                            $filename .= $fileTypeArray[1];
                            break;
                        case 'origname':
                            $filename .= $data['filename']['name'];
                            break;
                        case 'hash':
                            $filename .= hash_file('md5', $data['filename']['tmp_name']);
                            break;
                        case 'uservar':
                            $filename .= UserUtil::getVar($item_array[1]);
                            break;
                        case 'time':
                            $filename .= date($item_array[1]);
                            break;
                        default:
                            return LogUtil::registerError($this->__('Unknown tag found! Please check your module configuration at the upload filename.'));
                    }
                }
            }
            
            $result = Downloads_Util::uploadFile('filename', $storage, $filename);
            if (!$result) {
                return LogUtil::registerError($result);
            }
            $newFileUploadedFlag = true;
            $name = $data['filename']['name'];
            unset($data['filename']);
            $data['filename'] = $name;
            $data['url'] = "$storage/$filename";
        } else if (((is_array($data['filename'])) && (!$data['filename']['size'] > 0)) || (!isset($data['filename']))) {
            $data['filename'] = '';
        }

        // switch between edit and create mode
        if ($this->id) {
            if(SecurityUtil::checkPermission('Downloads::', '::', ACCESS_EDIT)) {
                $file = $this->entityManager->getRepository('Downloads_Entity_Download')->find($this->id);
                // if file is new, delete old one
                $oldname = $file->getUrl();
                if ($newFileUploadedFlag) {
                    $fullpath = DataUtil::formatForOS($oldname);
                    @unlink($fullpath);
                } else {
                    $data['filename'] = $file->getFilename();
                }
            } else {
                $view->setPluginErrorMsg('title', $this->__('You are not authorized to edit this entry!'));
                return false;
            }
        } else {
            $file = new Downloads_Entity_Download();
        }

        try {
            $file->merge($data);
            $this->entityManager->persist($file);
            $this->entityManager->flush();
        } catch (Zikula_Exception $e) {
            echo "<pre>";
            var_dump($e->getDebug());
            echo "</pre>";
            die;
        }

        ModUtil::apiFunc('Downloads', 'user', 'clearItemCache', $file);

        return $view->redirect($returnurl);
    }

}
