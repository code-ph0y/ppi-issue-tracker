<?php
class APP_Model_Ticket extends APP_Model_Application {

	protected $_table = 'ticket';
	protected $_primary = 'id';

	function __construct() {
		parent::__construct($this->_table, $this->_primary);
	}

    function getAddEditFormStructure($p_sMode = 'create', array $p_aOptions = array()) {
    	$structure = array(
            'fields' => array(
                    'title'            	    => array('type' => 'text', 'label' => 'Title', 'size' => 60),
                    'category_id'           => array('type' => 'dropdown', 'label' => 'Category', 'options' => array()),
                    'ticket_type'           => array('type' => 'dropdown', 'label' => 'Type', 'options' => array()),
            	    'severity'              => array('type' => 'dropdown', 'label' => 'Severity', 'options' => array()),
                    'status'                => array('type' => 'dropdown', 'label' => 'Status', 'options' => array()),
                    'version'               => array('type' => 'dropdown', 'label' => 'Version', 'options' => array()),
    		        'assigned_user_id'      => array('type' => 'dropdown', 'label' => 'Assign', 'options' => array()),
                    'content'               => array('type' => 'textarea', 'label' => 'Description', 'rows' => 10, 'cols' => 40),
					'submit'                => array('type' => 'submit', 'label' => '', 'value' => 'Create Ticket'),
    		),
            'rules' => array(
                    'title' => array('type' => 'required', 'message' => 'Title cannot be blank'),
                    'content' => array('type' => 'required', 'message' => 'You must enter a description')

            )
    	);

		if(isset($p_aOptions['isAdmin']) && $p_aOptions['isAdmin'] === false){
			unset($structure['fields']['assigned_user_id']);
			unset($structure['fields']['severity']);
			unset($structure['fields']['status']);
			unset($structure['fields']['version']);
		} else {
			$structure['fields']['severity']['options']         = array('minor' => 'minor','major' => 'major','critical' => 'critical');
			$structure['fields']['status']['options']           = array('open' => 'open', 'assigned' => 'assigned', 'closed' => 'closed');
		    $oUser                                              = new APP_Model_User();
		    $oTicket                                            = new APP_Model_Ticket();
    		$structure['fields']['version']['options']          = $this->convertGetListToDropdown($oTicket->getVersionsForFormStructure(), 'version');
    		$structure['fields']['assigned_user_id']['options'] = $this->convertGetListToDropdown($oUser->getList(), array('first_name', ' ', 'last_name'));
		}
		$oTicketCat = new APP_Model_Ticket_Category();
		$structure['fields']['ticket_type']['options']      = array('feature_request' => 'Feature request','bug' => 'Bug', 'enhancement' => 'Enhancement');
		$structure['fields']['category_id']['options']      = $this->convertGetListToDropdown($oTicketCat->getList(), 'title');

		return $structure;
    }

    function getTickets(array $p_aParams = array()) {

		$github = new Github_Client();
	    $tickets = $github->getIssueApi()->getList('dragoonis', $p_aParams['repo'], 'open');

	    foreach($tickets as $key => $ticket) {
			$ticket['id'] = $ticket['number'];
		    $ticket['status'] = $ticket['state'];
		    $ticket['ticket_type'] = 'bug';
		    $ticket['severity'] = 'major';
		    $user = $github->getUserApi()->show($ticket['user']);
		    $ticket['user_fullname'] = $user['name'];
		    $ticket['username'] = $user['login'];
		    $tickets[$key] = $ticket;
	    }
	    return $tickets;
/*
		$tickets = $this->select()
					->columns('t.*, u.first_name user_fn, u.last_name user_ln, uu.first_name user_assigned_fn, uu.last_name user_assigned_ln')
					->from($this->getTableName() . ' t')
					->leftJoin('users u', 't.user_id=u.id')
					->leftJoin('users uu', 't.assigned_user_id=uu.id');

        if(isset($p_aParams['filter'], $p_aParams['filter_type'])) {
            if($p_aParams['filter_type'] === 'cat') {
                $tickets->leftJoin('ticket_category tc', 'tc.id = t.category_id');
                $tickets->where('tc.title = ' . $this->quote(str_replace('-', ' ', $p_aParams['filter'])));
            }
			if($p_aParams['filter_type'] === 'mine') {
				$tickets->where('t.assigned_user_id = ' . $this->quote($p_aParams['filter']));
			}
			if($p_aParams['filter_type'] === 'version') {
			    $tickets->where('t.version = ' . $this->quote($p_aParams['filter']));
			}
        }

		if(isset($p_aParams['keyword']) && $p_aParams['keyword'] != '') {
			$sSecureSearchKeyword = $this->quote('%' . $p_aParams['keyword'] . '%');
			$aOrWhere             = array(
				't.id = '           . $sSecureSearchKeyword,
				't.title LIKE '     . $sSecureSearchKeyword,
				'ticket_type LIKE ' . $sSecureSearchKeyword,
				't.severity LIKE '  . $sSecureSearchKeyword,
				't.status LIKE '    . $sSecureSearchKeyword,
				't.status LIKE '    . $sSecureSearchKeyword,
				't.status LIKE '    . $sSecureSearchKeyword,
			);
			$tickets = $tickets->where(implode(' OR ', $aOrWhere));
		}
		$tickets = $tickets->where("status NOT IN('closed')")
			->order('created desc')
			->getList();

		return $tickets'
 */
    }
    function getTicket(array $p_aParams = array()) {
		$github = new Github_Client();
	    $ticket = $github->getIssueApi()->show('dragoonis', isset($p_aParams['repo']) ? $p_aParams['repo'] : 'ppi-framework', $p_aParams['id']);
//	    ppi_dump($ticket); exit;

		$ticket['id'] = $ticket['number'];
		$ticket['status'] = $ticket['state'];
		$ticket['ticket_type'] = 'bug';
		$ticket['severity'] = 'major';
	    $ticket['created'] = $ticket['created_at'];
	    $ticket['content'] = $ticket['body'];
		$user = $github->getUserApi()->show($ticket['user']);
		$ticket['user_fullname'] = $user['name'];
	    $ticket['repo_name'] = isset($p_aParams['repo']) ? $p_aParams['repo'] : 'ppi-framework';

	    return $ticket;
/*
		$tickets = $this->select()
					->columns('t.*, u.first_name user_fn, u.last_name user_ln, uu.first_name user_assigned_fn, uu.last_name user_assigned_ln, c.title category_name')
					->from($this->getTableName() . ' t')
					->leftJoin('ticket_category c', 't.category_id = c.id')
					->leftJoin('users u', 't.user_id=u.id')
					->leftJoin('users uu', 't.assigned_user_id=uu.id');

		if(isset($p_aParams['keyword']) && $p_aParams['keyword'] != '') {
			$sSecureSearchKeyword = $this->quote('%'. $p_aParams['keyword'] .'%');
			$aOrWhere             = array(
				't.id = '           . $sSecureSearchKeyword,
				't.title LIKE '     . $sSecureSearchKeyword,
				'ticket_type LIKE ' . $sSecureSearchKeyword,
				't.severity LIKE '  . $sSecureSearchKeyword,
				't.status LIKE '    . $sSecureSearchKeyword
			);
			$tickets = $tickets->where(implode(' OR ', $aOrWhere));
		}

		$tickets = $tickets
			->where('t.id = ' . $this->quote($p_aParams['id']))
			->order('created desc')
			->getList()->fetch();
		return $tickets;

 */
    }

    function getVersionsForFormStructure() {
        return $this->select()
            ->columns('id, version')
            ->from($this->_table)
            ->order('version')
            ->group('version')
            ->getList();
    }

}
