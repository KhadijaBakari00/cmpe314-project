Story: A student returns a book to a library kiosk. The system scans the book, validates if it
belongs to this library, and processes the return. If overdue, it calculates a fine. Once accepted,
the system updates the inventory and sends a notification. Finally, the system places the book
back on the shelf.
<img width="674" height="542" alt="Screenshot 2026-04-30 000053" src="https://github.com/user-attachments/assets/bd0ccecd-9990-467b-9b18-0309edc9058b" />

Normal Flow (Happy Path)

The system processes a book return when a student places a book on the kiosk scanner.
The system scans the book ID, validates it through the library database, and checks whether the book is returned on time. 
If the book is valid and not overdue, the inventory is updated, a confirmation notification is sent to the student, and the book is physically placed back on the shelf.
Finally, the system displays a successful return message.

Alternative Flow (Overdue Case)

If the book is overdue, the system calculates a fine using the FineCalculator and displays the amount to the student. 
The student must complete the payment through the PaymentSystem. 
After successful payment, the system continues the normal process by updating the inventory and completing the return.
If the payment is not completed, the return process is rejected.
