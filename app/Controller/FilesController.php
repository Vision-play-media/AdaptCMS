<?php

class FilesController extends AppController {
	public $name = 'Files';
	private $permissions;

	public $helpers = array(
		'Number'
	);
	public $file_types_editable = array(
		'txt',
		'php',
		'html',
		'css',
		'js',
		'phps',
		'htm'
	);

	public function beforeFilter()
	{
		parent::beforeFilter();

		$this->permissions = $this->getPermissions();
	}

	public function admin_index()
	{
		$conditions = array();

		if (!isset($this->params->named['trash'])) {
	        $conditions['File.deleted_time'] = '0000-00-00 00:00:00';
	    } else {
	        $conditions['File.deleted_time !='] = '0000-00-00 00:00:00';
        }

	    if ($this->permissions['any'] == 0)
	    {
	    	$conditions['User.id'] = $this->Auth->user('id');
	    }

        $this->paginate = array(
            'order' => 'File.created DESC',
            'limit' => $this->pageLimit,
            'conditions' => $conditions,
            'contain' => array(
            	'User'
            )
        );
        
        $this->request->data = $this->paginate('File');
	}

	public function admin_add($theme = null)
	{
		foreach ($this->file_types_editable as $ext) {
			$file_types[$ext] = $ext;
		}

		$media_list = $this->File->Media->find('list');

		$this->set(compact('file_types', 'media_list'));
		
		if (!empty($theme)) {
			$this->set(compact('theme'));
		}
		
        if ($this->request->is('post')) {
        	$this->request->data['File']['user_id'] = $this->Auth->user('id');

            if (!empty($this->request->data['File']['theme'])) {
            	$save = $this->File->themeFile($this->request->data['File']);
            	$redirect = array(
            		'controller' => 'themes', 
            		'action' => 'edit', 
            		$this->request->data['File']['theme']
            	);
            } else {
		    	if (!empty($this->request->data['File']['content'])) {
		    		$file = $this->slug($this->request->data['File']['file_name']).'.'.$this->request->data['File']['file_extension'];
		    		$path = WWW_ROOT.$this->request->data['File']['dir'].$file;

		        	$fh = fopen($path, 'w') or die("can't open file");
					fwrite($fh, $this->request->data['File']['content']);
					fclose($fh);

					$this->request->data['File']['filename'] = $file;
					$this->request->data['File']['mimetype'] = $this->File->mime_type($file);
					$this->request->data['File']['filesize'] = filesize($path);

				}

				unset($this->request->data['File'], $this->request->data['_Token']);

				foreach($this->request->data as $i => $row) {
					if (!strstr($row['File']['filename']['type'], 'image') && !empty($row['File']['library'])) {
						unset($this->request->data[$i]['Media']);
					}
				}

				// die(debug($this->request->data));

            	$save = $this->File->saveMany($this->request->data);
            	$redirect = array('action' => 'index');
            }

            if ($save) {
                $this->Session->setFlash('Your file has been upload.', 'flash_success');
                $this->redirect($redirect);
            } else {
                $this->Session->setFlash('Unable to upload your file.', 'flash_error');
            }
        }
	}

	public function admin_edit($id = null, $filename = null, $file_ext = null)
	{

		$this->File->id = $id;

		if (is_numeric($id)) {
	        $data = $this->File->find('first', array(
	        	'conditions' => array(
	        		'File.id' => $id
	        	),
	        	'contain' => array(
	        		'Media',
	        		'User'
	        	)
	        ));

	        if ($data['User']['id'] != $this->Auth->user('id') && $this->permissions['any'] == 0)
	        {
                $this->Session->setFlash('You cannot access another users item.', 'flash_error');
                $this->redirect(array('action' => 'index'));	        	
	        }

	        $file = WWW_ROOT.
	        		$data['File']['dir'].
	        		$data['File']['filename'];
	        $data['info'] = getimagesize($file);
	        $data['media-list'] = $this->File->Media->find('list');
	    } elseif (!empty($filename)) {
	    	$ex = explode("-", $id);
	    	$ex2 = explode("___", $filename);
	    	$file_ext = str_replace("_",".", $file_ext);

	    	if (!empty($ex[1])) {
	    		if (!empty($ex2[1])) {
	    			$file_location = $ex2[0].'/'.$ex2[1].'.'.$file_ext;
				} else {
					$file_location = $filename.'.'.$file_ext;
				}

	    		if ($ex[1] == "Default") {
	    			$path = WWW_ROOT.$file_location;
	    		} else {
	    			$path = WWW_ROOT.'themes/'.$ex[1].'/'.$file_location;
	    		}

	    		if (file_exists($path)) {
	    			$file = $path;
	    		}
			}

			if ($this->request->is('get')) {
				$data['File']['filename'] = basename($path);
			} else {
				$data = null;
			}

			$this->set('location', $path);
			$this->set('theme', $ex[1]);
	    }

	    if (empty($data)) {
	    	$data = array();
	    }

	    $this->set('data', $data);

		if (!empty($file) && is_readable($file)) {
	        $ext = pathinfo(
	        		$file, 
	        		PATHINFO_EXTENSION
	        );

	        if (in_array($ext, $this->file_types_editable)) {
		 		$handle = fopen($file, "r");

		 		if (filesize($file) > 0) {
					$this->set('file_contents', fread($handle, filesize($file)));
	    		}
	        }
	    }

	    if ($this->request->is('post')) {
	    	$this->request->data['File']['user_id'] = $this->Auth->user('id');

	    	if (!empty($this->request->data['File']['theme'])) {
	    		if ($this->request->data['File']['old_filename'] != $this->request->data['File']['filename']) {
	    			$path = str_replace(
	    				$this->request->data['File']['old_filename'],
	    				$this->request->data['File']['filename'],
	    				$this->request->data['File']['location']
	    			);

	    			rename($this->request->data['File']['location'], $path);
	    		} else {
	    			$path = $this->request->data['File']['location'];
	    		}
	    	
	    		if (!empty($this->request->data['File']['content'])) {
		        	$fh = fopen($path, 'w') or die("can't open file");
					fwrite($fh, $this->request->data['File']['content']);
					fclose($fh);
				}

				$save = true;
	    		$redirect = array(
	    			'controller' => 'themes', 
	    			'action' => 'edit', 
	    			$this->request->data['File']['theme']
	    		);
	    	} else {
		    	if (!empty($this->request->data['File']['content'])) {
		        	$fh = fopen(WWW_ROOT.$this->request->data['File']['dir'].$this->request->data['File']['filename'], 'w') or die("can't open file");
					fwrite($fh, $this->request->data['File']['content']);
					fclose($fh);
				}

				$save = $this->File->saveAll($this->request->data);
				$redirect = array('action' => 'index');
			}

	        if ($save) {
	            $this->Session->setFlash('Your file has been updated.', 'flash_success');
	            $this->redirect($redirect);
	        } else {
	            $this->Session->setFlash('Unable to update your file.', 'flash_error');
	        }
	    }
	}

	public function admin_delete($id = null, $permanent = null)
	{
		if ($this->request->is('post')) {
	        throw new MethodNotAllowedException();
	    }

	    if (is_numeric($id)) {
		    $file = $this->File->find('first', array(
		    	'conditions' => array(
		    		'File.id' => $id
	    		),
		    	'fields' => array(
		    		'filename,user_id'
		    	)
		    ));

		    $this->File->id = $id;

	        if ($data['File']['user_id'] != $this->Auth->user('id') && $this->permissions['any'] == 0)
	        {
	            $this->Session->setFlash('You cannot access another users item.', 'flash_error');
	            $this->redirect(array('action' => 'index'));	        	
	        }

	        if (!empty($permanent)) {
	            $delete = $this->File->delete($id);
			    if (file_exists(WWW_ROOT.'uploads/'.$file['File']['filename']) && 
			    	is_file(WWW_ROOT.'uploads/'.$file['File']['filename'])) {
			    		unlink(WWW_ROOT.'uploads/'.$file['File']['filename']);

			    	if (file_exists(WWW_ROOT.'uploads/thumb/'.$file['File']['filename']) && 
			    		is_file(WWW_ROOT.'uploads/thumb/'.$file['File']['filename'])) {
			    			unlink(WWW_ROOT.'uploads/thumb/'.$file['File']['filename']);
			    	}
			    }
	        } else {
	            $delete = $this->File->saveField('deleted_time', $this->File->dateTime());
	        }
	    $redirect = array('action' => 'index');
	    } else {
	    	$ex = explode("-", $id);
	    	$ex2 = explode("___", $permanent);

	    	if (!empty($ex[1])) {
	    		if (!empty($ex2[1])) {
	    			$file_location = $ex2[0].'/'.$ex2[1];
				} else {
					$file_location = $permanent;
				}

	    		if ($ex[1] == "Default") {
	    			$path = WWW_ROOT.$file_location;
	    		} else {
	    			$path = WWW_ROOT.'themes/'.$ex[1].'/'.$file_location;
	    		}

	    		if (file_exists($path)) {
	    			$delete = unlink($path);
	    		}
			}

			$file['File']['filename'] = $file_location;
			$redirect = array('controller' => 'themes', 'action' => 'edit', $ex[1]);
	    }

	    if ($delete) {
	        $this->Session->setFlash('The file `'.$file['File']['filename'].'` has been deleted.', 'flash_success');
	        $this->redirect($redirect);
	    } else {
	    	$this->Session->setFlash('The file `'.$file['File']['filename'].'` has NOT been deleted.', 'flash_error');
	        $this->redirect($redirect);
	    }
	}

    public function admin_restore($id = null, $title = null)
    {
        if ($this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $this->File->id = $id;

        $data = $this->File->find('first', array(
        	'conditions' => array(
        		'File.id' => $id
        	),
        	'contain' => array(
    			'User'
        	)
        ));
        if ($data['User']['id'] != $this->Auth->user('id') && $this->permissions['any'] == 0)
        {
            $this->Session->setFlash('You cannot access another users item.', 'flash_error');
            $this->redirect(array('action' => 'index'));	        	
        }

        if ($this->File->saveField('deleted_time', '0000-00-00 00:00:00')) {
            $this->Session->setFlash('The file `'.$title.'` has been restored.', 'flash_success');
            $this->redirect(array('action' => 'index'));
        } else {
            $this->Session->setFlash('The file `'.$title.'` has NOT been restored.', 'flash_error');
            $this->redirect(array('action' => 'index'));
        }
    }
}