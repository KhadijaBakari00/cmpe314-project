The activity diagram describes the workflow of a Library Kiosk when a student returns a book. 
First, the student places the book on the scanner, and the system scans the book ID and validates it using the library database.
If the book is invalid, the system displays an error message and rejects the return process. 
If the book is valid, the system checks whether the book is overdue.

If the book is overdue, the system calculates a fine and asks the student to pay it. 
If the student does not pay the fine, the return is rejected and the process stops. 
If the fine is paid successfully, the payment is recorded. 
If the book is not overdue, the process continues without a fine.

After validation and payment handling, the system performs several actions in parallel: updating the inventory, sending a return confirmation notification, and physically placing the book back on the shelf. 
Finally, the return process is completed successfully.
<img width="570" height="749" alt="Activity1" src="https://github.com/user-attachments/assets/2f2dddbc-ae97-4fda-9d0e-f881c638d8c6" />
