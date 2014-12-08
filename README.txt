README.TXT

CSC309 ASSIGNMENT3
Tyler Pham
G3phamty
999643382

ID of AMI:
Location of source files in AMI:

Please use chrome for playing

This is my implemention of the popular 70's game connect4,

for a clearer explanation of how the code works I will be using user1 and user2 to explain the flow

-When a user logs in there will be a list of all available users online.

-Users can send requessts by clicking on others usernames.

-In the board controller index function, I have added code to store a representation of the board in an array. 
	0, represent spaces in the board where no tokens have been placed.

-the data is then sent using json to the server in an array. This array stores
	3 elements vital to the running of the game the three elements include(the board array itself, the current turn, and the 'state of the game')

- in the board view the function get_board is running repeatedly on a loop using setInterval.

- in the function game_board the data array will be pulled from the server using json and the board will be updated to reflect the new server changes.

-get_board will then check the four conditions to stop the loop. A tie, user1 or user2 has won, or the game is still active and has yet found a winner.

-the array is represented in the view, as a html table, each slot given a class to represent if it has been used "free_slot" and a slot coordinate to represent where it belongs relative to the board.

-upon clicking an empty slot only when it is your turn the helper functions will retrieve the coordinates of the click and update the gameboard. After the users turn the gameboard is then sent back to the server to be checked for a victor. Repeat until the game is done

Please note that games must be completed when they are started, you cannot leave prematurely or the program will break.

Some bugs include: 
-after a game is finished both users must logout and resign in from the login
screen in order to play another game.