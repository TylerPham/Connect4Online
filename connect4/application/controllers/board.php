<?php

class Board extends CI_Controller {
     
    function __construct() {
    		// Call the Controller constructor
	    	parent::__construct();
	    	session_start();
    } 
          
    public function _remap($method, $params = array()) {
	    	// enforce access control to protected functions	
    		
    		if (!isset($_SESSION['user']))
   			redirect('account/loginForm', 'refresh'); //Then we redirect to the index page again
 	    	
	    	return call_user_func_array(array($this, $method), $params);
    }
    
    
    function index() {
		$user = $_SESSION['user'];
    		    	
	    	$this->load->model('user_model');
	    	$this->load->model('invite_model');
	    	$this->load->model('match_model');
	    	
	    	$user = $this->user_model->get($user->login);

	    	$invite = $this->invite_model->get($user->invite_id);
	    	
	    	if ($user->user_status_id == User::WAITING) {
	    		$invite = $this->invite_model->get($user->invite_id);
	    		$otherUser = $this->user_model->getFromId($invite->user2_id);
	    	}
	    	else if ($user->user_status_id == User::PLAYING) {
	    		$match = $this->match_model->get($user->match_id);
	    		if ($match->user1_id == $user->id)
	    			$otherUser = $this->user_model->getFromId($match->user2_id);
	    		else
	    			$otherUser = $this->user_model->getFromId($match->user1_id);

	    		$board_array = array(

	    		array(0, 0, 0, 0, 0, 0, 0),
				array(0, 0, 0, 0, 0, 0, 0),
				array(0, 0, 0, 0, 0, 0, 0),
				array(0, 0, 0, 0, 0, 0, 0),
				array(0, 0, 0, 0, 0, 0, 0),
				array(0, 0, 0, 0, 0, 0, 0));

			$inviter = $this->user_model->getFromId($match->user2_id)->login;


			$boardArray = array($board_array, $inviter, 'play');

			$json_board_array = json_encode($boardArray);

			$this->match_model->updateBoard($match->id, $json_board_array);
	    	}
	    	
	    	$data['user']=$user;
	    	$data['otherUser']=$otherUser;

	    	switch($user->user_status_id) {
	    		case User::PLAYING:	
	    			$data['status'] = 'playing';
	    			break;
	    		case User::WAITING:
	    			$data['status'] = 'waiting';
	    			break;
	    	}
	    	
		$this->load->view('match/board',$data);
    }

 	function postMsg() {
 		$this->load->library('form_validation');
 		$this->form_validation->set_rules('msg', 'Message', 'required');
 		
 		if ($this->form_validation->run() == TRUE) {
 			$this->load->model('user_model');
 			$this->load->model('match_model');

 			$user = $_SESSION['user'];
 			 
 			$user = $this->user_model->getExclusive($user->login);
 			if ($user->user_status_id != User::PLAYING) {	
				$errormsg="Not in PLAYING state";
 				goto error;
 			}
 			
 			$match = $this->match_model->get($user->match_id);			
 			
 			$msg = $this->input->post('msg');
 			
 			if ($match->user1_id == $user->id)  {
 				$msg = $match->u1_msg == ''? $msg :  $match->u1_msg . "\n" . $msg;
 				$this->match_model->updateMsgU1($match->id, $msg);
 			}
 			else {
 				$msg = $match->u2_msg == ''? $msg :  $match->u2_msg . "\n" . $msg;
 				$this->match_model->updateMsgU2($match->id, $msg);
 			}
 				
 			echo json_encode(array('status'=>'success'));
 			 
 			return;
 		}
		
 		$errormsg="Missing argument";
 		
		error:
			echo json_encode(array('status'=>'failure','message'=>$errormsg));
 	}

	function getMsg() {
 		$this->load->model('user_model');
 		$this->load->model('match_model');
 			
 		$user = $_SESSION['user'];
 		 
 		$user = $this->user_model->get($user->login);
 		if ($user->user_status_id != User::PLAYING) {	
 			$errormsg="Not in PLAYING state";
 			goto error;
 		}
 		// start transactional mode  
 		$this->db->trans_begin();
 			
 		$match = $this->match_model->getExclusive($user->match_id);			
 			
 		if ($match->user1_id == $user->id) {
			$msg = $match->u2_msg;
 			$this->match_model->updateMsgU2($match->id,"");
 		}
 		else {
 			$msg = $match->u1_msg;
 			$this->match_model->updateMsgU1($match->id,"");
 		}

 		if ($this->db->trans_status() === FALSE) {
 			$errormsg = "Transaction error";
 			goto transactionerror;
 		}
 		
 		// if all went well commit changes
 		$this->db->trans_commit();
 		
 		echo json_encode(array('status'=>'success','message'=>$msg));
		return;
		
		transactionerror:
		$this->db->trans_rollback();
		
		error:
		echo json_encode(array('status'=>'failure','message'=>$errormsg));
 	}

 	function get_board() {
 		$this->load->model('user_model');
 		$this->load->model('match_model');
 			
 		$user = $_SESSION['user'];
 		 
 		$user = $this->user_model->get($user->login);

 		if ($user->user_status_id == User::WAITING) {	
			$errormsg="Not in PLAYING state";
			echo json_encode(array('status'=>'failure','message'=>$errormsg));
		}

 		$match = $this->match_model->getExclusive($user->match_id);
 			
 		if($match != null) {
 			$json_board_array = $match->board_state;
 	
 			$boardArray = json_decode($json_board_array);
 			echo $json_board_array;
	
 			return;
 		}
 	}

 	function post_board() {

		$this->load->model('match_model');
		$this->load->model('user_model');

		$user = $_SESSION['user'];
		$user = $this->user_model->getExclusive($user->login);

		if ($user->user_status_id != User::PLAYING) {	
		$errormsg="Not in PLAYING state";
		echo json_encode(array('status'=>'failure','message'=>$errormsg));
		break;
			//goto error;
		}

		$board_array = $this->input->post('array'); 
		$username = $this->input->post('username');

		$winner = $this->get_winner($board_array);

		$boardArray = array($board_array, $username, $winner);
		$json_board_array = json_encode($boardArray);

		//ci transactions
		$this->db->trans_begin();
		
		$match = $this->match_model->get($user->match_id);			
	
		$this->match_model->updateBoard($match->id, $json_board_array);

		$active = 1;
		$u1win = 2;
		$u2win = 3;
		$tie = 4;

		$user_status = 2;

		if ($winner == 'tie') {
			$status = $tie;
			$this->user_model->updateStatus($match->user1_id, $user_status);
			$this->user_model->updateStatus($match->user2_id, $user_status);
		}
		else if ($winner == 'play') {
			$status = $active;
		}
		else if ($winner == $match->user1_id) {
			$status = $u1win;
			$this->user_model->updateStatus($match->user1_id, $user_status);
			$this->user_model->updateStatus($match->user2_id, $user_status);
		}
		else {
			$status = $u2win;
			$this->user_model->updateStatus($match->user1_id, $user_status);
			$this->user_model->updateStatus($match->user2_id, $user_status);
		}

		$this->match_model->updateStatus($match->id, $status);

		if ($this->db->trans_status() === FALSE) {
			$errormsg = "Transaction error";

			echo json_encode(array('status'=>'failure','message'=>$errormsg));
			$this->db->trans_rollback();
			break;
		}
		
		// if all went well commit changes
		$this->db->trans_commit();
		echo json_encode(array('status'=>'success', 'winner'=>$winner));
		 
		return;
 	}

 	function get_winner($board) {
 		/*Unless otherwise, the game is always a tie*/
 		$is_tie = TRUE;
 		foreach($board as $row_ind => $row) {
 			foreach($row as $col_ind => $item) {
 				/*If a piece of the game board is not filled in with anything
 				Then it cannot be a tie*/
 				if($item == 0){
 					$is_tie = FALSE;
 				}


/*These blocks contain the winning conditions of the game. Each checking each type of victory
-A Vertical line
-A Horizontal line
-A Diagonal going right
-A Dioagonal going left
*/
	if(
		$board[$row_ind][$col_ind] != 0 && $row_ind < 3 &&
		
		$board[$row_ind][$col_ind] == $board[$row_ind+1][$col_ind] && 
		$board[$row_ind+1][$col_ind] == $board[$row_ind+2][$col_ind] &&
		$board[$row_ind+2][$col_ind] == $board[$row_ind+3][$col_ind])
		{
		return $board[$row_ind][$col_ind];
		}

// diagonal going left
	if(
		$board[$row_ind][$col_ind] != 0 && $row_ind < 3 && $col_ind < 4 &&

		$board[$row_ind][$col_ind] == $board[$row_ind+1][$col_ind+1] && 
		$board[$row_ind+1][$col_ind+1] == $board[$row_ind+2][$col_ind+2] &&
		$board[$row_ind+2][$col_ind+2] == $board[$row_ind+3][$col_ind+3]) {
			return $board[$row_ind][$col_ind];
		}

// diagonal going right
	if(
		$board[$row_ind][$col_ind] != 0 && $row_ind < 3 && $col_ind > 2 &&

		$board[$row_ind][$col_ind] == $board[$row_ind+1][$col_ind-1] && 
		$board[$row_ind+1][$col_ind-1] == $board[$row_ind+2][$col_ind-2] &&
		$board[$row_ind+2][$col_ind-2] == $board[$row_ind+3][$col_ind-3]) {
			return $board[$row_ind][$col_ind];
		}
// horizontal check
	if(
		$board[$row_ind][$col_ind] != 0  && $col_ind < 4 &&
		$board[$row_ind][$col_ind] == $board[$row_ind][$col_ind+1] && 
		$board[$row_ind][$col_ind+1] == $board[$row_ind][$col_ind+2] &&
		$board[$row_ind][$col_ind+2] == $board[$row_ind][$col_ind+3]) {
			return $board[$row_ind][$col_ind];
				}
			}
		}
		if ($is_tie){
			return 'tie';
		} 
		else{
			/*Keep playing as no one has won the game*/
			return 'play';
		}
	}


//Past functions before I hardcoded the winning conditions
	
// function check_vertical_win($board,$row_ind, $col_ind){

// 	$count = 0;
// 	if ($row_ind < 3){
// 		while($board[$row_ind][$col_ind] == 1){
// 			$count++;
// 			$row_ind--;
// 			if($count >= 4){
// 				// return $board[$row_id, $col_id];
// 				return true;
// 			}
// 		}
// 	}
// 	return false;
// }

// function check_horizontal_win($board, $row_ind, $col_ind){
// 	$count = 0;
// 	if ($col_ind < 4){
// 		while($board[$row_ind][$col_ind] == 1){
// 			$count++;
// 			$col_ind++;
// 			if($count >= 4){
// 				// return $board[$row_id, $col_id];
// 				return true;}
// 		}
// 	}
// 	return false;
// }

// function check_left_diag_win($board,$row_ind, $col_ind){
// 	$count = 0;
// 	if($col_ind > 2 && $row_ind < 3){
// 		while($board[$row_ind][$col_ind] == 1){
// 			$count++;
// 			$row_ind++;
// 			$col_ind--;
// 			if($count >= 4){
// 				// return $board[$row_id, $col_id];
// 				return true;}
// 		}
// 	}
// 	return false;
// }

// function check_right_diag_win($board, $row_ind, $col_ind){
// 	$count = 0;
// 	if($col_ind < 3 && $row_ind < 3){
// 		while($board[$row_ind][$col_ind]){
// 			$count++;
// 			$row_ind++;
// 			$col_ind++;
// 			if($count >= 4){
// 				// return $board[$row_id, $col_id];
// 				return true;}
// 		}

// 	}
// 	return false;
// }

 	
 }

