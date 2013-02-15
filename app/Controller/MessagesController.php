<?php

class MessagesController extends AppController
{
	public $name = 'Messages';
	private $boxes = array(
		'inbox',
		'outbox',
		'sentbox',
		'archive'
	);

	public function index($box_slug = null)
	{
		if (!empty($box_slug) && in_array($box_slug, $this->boxes) && $box_slug != 'inbox') {
			if ($box_slug == "archive")
			{
				$conditions = array(
					'OR' => array(
						array(
							'AND' => array(
								'Message.sender_user_id' => $this->Auth->user('id'),
								'Message.sender_archived_time !=' => '0000-00-00 00:00:00'
							)
						),
						array(
							'AND' => array(
								'Message.receiver_user_id' => $this->Auth->user('id'),
								'Message.receiver_archived_time !=' => '0000-00-00 00:00:00'
							)
						)
					)
	            );
			} else {
				$conditions = array(
	            	'Message.sender_archived_time' => '0000-00-00 00:00:00',
	            	'Message.sender_user_id' => $this->Auth->user('id')
	            );

	            if ($box_slug == "outbox")
	            {
	            	$conditions['Message.is_read'] = 0; 
	            } elseif ($box_slug == "sentbox")
	           	{
	            	$conditions['Message.is_read'] = 1; 
	           	}
	        }
        } else {
			$conditions = array(
            	'Message.receiver_archived_time' => '0000-00-00 00:00:00',
            	'Message.parent_id' => 0,
            	'Message.receiver_user_id' => $this->Auth->user('id')
            );
            $box_slug = 'inbox';
        }

		$this->paginate = array(
            'order' => 'Message.created DESC',
            'limit' => 10,
            'conditions' => $conditions,
            'contain' => array(
				'Receiver',
				'Sender'
			)
        );
        
		$this->request->data = $this->paginate('Message');
		$this->set( 'box', $box_slug );
	}

	public function view($id = null)
	{
		$this->request->data = $this->Message->find('all', array(
			'conditions' => array(
				'OR' => array(
					'Message.id' => $id,
					'Message.parent_id' => $id
				)
			),
			'contain' => array(
				'Receiver',
				'Sender'
			)
		));

		if ($this->request->data[0]['Message']['sender_user_id'] != $this->Auth->user('id') &&
			$this->request->data[0]['Message']['receiver_user_id'] != $this->Auth->user('id'))
		{
			$this->redirect(array('action' => 'index'));
		}

		foreach($this->request->data as $row)
		{
			if ($row['Message']['is_read'] == 0 && $row['Receiver']['id'] == $this->Auth->user('id'))
			{
				$this->Message->id = $row['Message']['id'];
				$this->Message->saveField('is_read', 1);
			}
		}

		$this->set('subject', $this->request->data[0]['Message']['title']);
		$this->set('sender', $this->request->data[0]['Sender']['id']);
	}

	public function send()
	{
		if (!empty($this->request->data))
		{
			$this->request->data['Message']['sender_user_id'] = $this->Auth->user('id');

			if ($this->RequestHandler->isAjax())
			{
		    	$this->layout = 'ajax';
		    	$this->autoRender = false;

		    	$this->Message->updateAll(
		    		array(
		    			'Message.last_reply_time' => '"' . $this->Message->dateTime() . '"'
		    		),
		    		array(
		    			'OR' => array(
		    				'Message.id' => $this->request->data['Message']['parent_id'],
		    				'Message.parent_id' => $this->request->data['Message']['parent_id']
		    			)
		    		)
		    	);
			}

            if ($this->Message->save($this->request->data))
            {
            	if ($this->layout == 'ajax')
            	{
            		return json_encode(array(
            			'status' => true
            		));
            	} else {
	                $this->Session->setFlash('Your message has been sent.', 'flash_success');
	                $this->redirect(array('action' => 'index'));
	            }
            } else {
            	if ($this->layout == 'ajax')
            	{
            		return json_encode(array(
            			'status' => false
            		));
            	} else {
                	$this->Session->setFlash('Unable to send message. Fix the errors below.', 'flash_error');
               	}
            }
		}
	}

	public function move($action = null, $id = null)
	{
		$this->request->data = $this->Message->findById($id);

		if ($this->request->data['Message']['sender_user_id'] != $this->Auth->user('id') &&
			$this->request->data['Message']['receiver_user_id'] != $this->Auth->user('id'))
		{
			$this->redirect(array('action' => 'index'));
		}

		$this->Message->id = $id;

		if ($this->request->data['Message']['receiver_user_id'] == $this->Auth->user('id') && $action == "archive")
		{
			$save = $this->Message->saveField('receiver_archived_time', $this->Message->dateTime());
			$msg = 'archived';
			$box = 'archive';

		} elseif($this->request->data['Message']['receiver_user_id'] == $this->Auth->user('id') && $action == "inbox")
		{
			$save = $this->Message->saveField('receiver_archived_time', '0000-00-00 00:00:00');
			$msg = 'moved to the inbox';
			$box = 'inbox';

		} elseif ($this->request->data['Message']['sender_user_id'] == $this->Auth->user('id') && $action == "archive")
		{
			$save = $this->Message->saveField('sender_archived_time', $this->Message->dateTime());
			$msg = 'archived';
			$box = 'archive';

		} elseif($this->request->data['Message']['sender_user_id'] == $this->Auth->user('id') && $action == "inbox")
		{
			$save = $this->Message->saveField('sender_archived_time', '0000-00-00 00:00:00');
			$msg = 'moved to the inbox';
			$box = 'inbox';

		} elseif($this->request->data['Message']['receiver_user_id'] == $this->Auth->user('id') && $action == "mark_read")
		{
			$save = $this->Message->saveField('is_read', '1');
			$msg = 'marked read';
			$box = 'inbox';
		}

        if ($save) {
            $this->Session->setFlash('The message has been ' . $msg . '.', 'flash_success');
            $this->redirect(array('action' => 'index', $box));
        } else {
            $this->Session->setFlash('The message could not be ' . $msg . '.', 'flash_error');
            $this->redirect(array('action' => 'index', $box));
        }
	}
}