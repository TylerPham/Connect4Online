
<!DOCTYPE html>

<html>
	<head>
		<meta charset="utf-8" />
    	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	
	   	<link rel="stylesheet" href="<?php echo base_url("css/board_style.css"); ?>" />
    	<script src="<?php echo base_url("js/vendor/modernizr.js"); ?>"></script>
    	<script src="<?php echo base_url("js/vendor/jquery.js"); ?>"></script>
	
		<script src="http://code.jquery.com/jquery-latest.js"></script>
		<script src="<?= base_url() ?>/js/jquery.timers.js"></script>
		<script>

		var otherUser = "<?= $otherUser->login ?>";
		var user = "<?= $user->login ?>";
		var user_id = "<?= $user->id ?>";
		var status = "<?= $status ?>";
		
		var user_chip = user_id;
		
		$(function(){
			/*$('body').everyTime(2000,function(){
					if (status == 'waiting') {
						$.getJSON('<?= base_url() ?>arcade/checkInvitation',function(data, text, jqZHR){
								if (data && data.status=='rejected') {
									alert("Sorry, your invitation to play was declined!");
									window.location.href = '<?= base_url() ?>arcade/index';
								}
								if (data && data.status=='accepted') {
									status = 'playing';
									$('#status').html('Playing ' + otherUser);
								}
								
						});
					}
					var url = "<?= base_url() ?>board/getMsg";
					$.getJSON(url, function (data,text,jqXHR){
						if (data && data.status=='success') {
							var conversation = $('[name=conversation]').val();
							var msg = data.message;
							if (msg.length > 0)
								$('[name=conversation]').val(conversation + "\n" + otherUser + ": " + msg);
						}
					});
			});

			$('form').submit(function(){
				var arguments = $(this).serialize();
				var url = "<?= base_url() ?>board/postMsg";
				$.post(url,arguments, function (data,textStatus,jqXHR){
						var conversation = $('[name=conversation]').val();
						var msg = $('[name=msg]').val();
						$('[name=conversation]').val(conversation + "\n" + user + ": " + msg);
						});
				return false;
				});*/

			/*These globals are used throughout the code*/
			board_array = null;
			user_turn = null;
			show_alert = true;
			show_alert2 = true;

			function update_board_slots(){
				var y = 0;
				for (; y < board_array.length; y++) {

					var x = 0;
					for (; x < board_array[y].length; x++) {

						/*Check in the board if the pos is used and if it belongs to the oppenent
						so you can mark it as the opponents chip*/
						if (board_array[y][x] != 0){
							if(board_array[y][x] != user_chip){
							/*find the correct slot position by appending the strings together to get
							the right slot coords
							slot_num = slotYX*/
							slot_num = '#slot' + (y + '' + x);

							/*remove the class free_slot since its not free*/
							$(slot_num).removeClass('free_slot');
							// replace with opp_chip class since its used by opp
							$(slot_num).addClass('opp_chip');
							}
						}
					}
				}
			};


			function get_board(url){
				//Grab data array
				$.getJSON(url, function(data){
					board_array = data[0];
					user_turn = data[1];
					winner = data[2];

					$("#curr_move").html(user_turn);

					// update the board for the user
					if(user_turn == user){
						update_board_slots();
					}

					// Order of operations must be
					// 1. check for a tie first as it will be the first thing  
					// 2. if neither users have won
					// 3. check user1 (arbitrary check)
					// 4. check user2

					if (winner == 'tie'){
						window.clearInterval(game_loop);
						alert('Game Tied!');
						window.location.assign("<?= base_url() ?>");
					}
					else if(winner == 'play'){

					}

					else if(winner == user_chip){

						window.clearInterval(game_loop);

						if(show_alert == true){
							alert('You win you beat ' + otherUser + '!');
							show_alert = false;

						}
						window.location.assign("<?= base_url() ?>");
					}
					else{
						window.clearInterval(game_loop);
						
						if(show_alert2 == true){
									alert(otherUser + ' has won the game, better luck next time');
									show_alert = false;
						}
						window.location.assign("<?= base_url() ?>");
					}
					
				});
			}
			var game_loop = setInterval(function(){
				get_board("<?= base_url() ?>board/get_board");
			}, 20);


			function whichslot(col, board_array){
				for (var row = board_array.length - 1; row >= 0; row--) {
					if (board_array[row][col] == 0){
						return {
							'row': row,
							'col': col,
							'targetId': '#slot' + row + '' + col
						};
					};
				}
				return null;
			}

			function get_slot_col(slot){
				slot_id = slot.attr('id');
				return slot_id.charAt(slot_id.length - 1);
			}

			function hoverslotColor(slot, color){
				col = get_slot_col(slot);
				
				try{
					targetId = whichslot(col, board_array).targetId;
					if (color){
						$(targetId).removeClass('free_slot');
						}
					else{
						$(targetId).addClass('free_slot');
						}
				} 
			}


			//Clicking things

			$('#indicator').css('left', $('.board').position().left);

			$('.slot').hover(
				function(){
					$('#indicator').css({'left': $(this).position().left, 'display': 'inline-block'});
					hoverslotColor($(this), true);
					// $(this).fadeOut(100);
					// $(this).fadeIn(500);

				}, 
				function(){
					$('#indicator').css('display', 'none');
					hoverslotColor($(this), false);
				});

			$('.slot').click(
				function(){
					/*If it is your turn find out the pos on the grid where the user clicks*/
					if (user == user_turn) {
						col = get_slot_col($(this));
						target = whichslot(col, board_array);

						if(target){
							board_array[target.row][target.col] = user_chip;
							$(target.targetId).removeClass('free_slot').addClass('user_chip');
							if(target.row-1 >= 0){
								hoverslotColor($('#slot' + (target.row-1) + '' + target.col), true);
							}
							user_turn = otherUser;
							$.post("<?= base_url() ?>board/post_board", 
								{'array': board_array, 'username': otherUser}, 
								function(data){

									var winner = data.winner;
									if(winner){
										if (winner == 'tie'){
											window.clearInterval(game_loop);
											window.location.assign("<?= base_url() ?>");
										}
										else if(winner == 'play'){
										}
										else if(winner == user_chip){
											window.clearInterval(game_loop);
											window.location.assign("<?= base_url() ?>");
										}
										else{
										}
									}
								}, "json");
						}
					else{
						alert("No more space in column");
					}
				} 
				else{
					alert("Please wait,your opponent is making his/her move!");
				}
			}
		);
	}
);
	
	</script>



	</head> 
	<body>  
			<h1>CONNECT 4 ONLINE</h1>

		<h4> Greetings <?= $user->login ?>
		<?= anchor('account/logout','Logout', 'class="button"') ?> </h4>
		<h3> It's <span id="curr_move"></span> turn </h3>

		<span class="curr_token" id="indicator"></span>
		
		<table class="board">
			<tr>
				<td class="slot free_slot" id="slot00"></td>
				<td class="slot free_slot" id="slot01"></td>
				<td class="slot free_slot" id="slot02"></td>
				<td class="slot free_slot" id="slot03"></td>
				<td class="slot free_slot" id="slot04"></td>
				<td class="slot free_slot" id="slot05"></td>
				<td class="slot free_slot" id="slot06"></td>
			</tr>
			<tr></tr>
			<tr>
				<td class="slot free_slot" id="slot10"></td>
				<td class="slot free_slot" id="slot11"></td>
				<td class="slot free_slot" id="slot12"></td>
				<td class="slot free_slot" id="slot13"></td>
				<td class="slot free_slot" id="slot14"></td>
				<td class="slot free_slot" id="slot15"></td>
				<td class="slot free_slot" id="slot16"></td>
			</tr>
			<tr></tr>
			<tr>
				<td class="slot free_slot" id="slot20"></td>
				<td class="slot free_slot" id="slot21"></td>
				<td class="slot free_slot" id="slot22"></td>
				<td class="slot free_slot" id="slot23"></td>
				<td class="slot free_slot" id="slot24"></td>
				<td class="slot free_slot" id="slot25"></td>
				<td class="slot free_slot" id="slot26"></td>
			</tr>
			<tr></tr>
			<tr>
				<td class="slot free_slot" id="slot30"></td>
				<td class="slot free_slot" id="slot31"></td>
				<td class="slot free_slot" id="slot32"></td>
				<td class="slot free_slot" id="slot33"></td>
				<td class="slot free_slot" id="slot34"></td>
				<td class="slot free_slot" id="slot35"></td>
				<td class="slot free_slot" id="slot36"></td>
			</tr>
			<tr></tr>
			<tr>
				<td class="slot free_slot" id="slot40"></td>
				<td class="slot free_slot" id="slot41"></td>
				<td class="slot free_slot" id="slot42"></td>
				<td class="slot free_slot" id="slot43"></td>
				<td class="slot free_slot" id="slot44"></td>
				<td class="slot free_slot" id="slot45"></td>
				<td class="slot free_slot" id="slot46"></td>
			</tr>
			<tr></tr>
			<tr>
				<td class="slot free_slot" id="slot50"></td>
				<td class="slot free_slot" id="slot51"></td>
				<td class="slot free_slot" id="slot52"></td>
				<td class="slot free_slot" id="slot53"></td>
				<td class="slot free_slot" id="slot54"></td>
				<td class="slot free_slot" id="slot55"></td>
				<td class="slot free_slot" id="slot56"></td>
			</tr>
		</table>
	</div>

		<h4>You are: RED</h4>
		<h4>Opponent is: BLACK </h4>
	</body>


</html>

