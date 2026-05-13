<img width="1138" height="444" alt="Screenshot 2026-05-13 232204" src="https://github.com/user-attachments/assets/acaf216e-40db-40f5-815c-105dcba89fec" />
Context Diagram (Level 0 DFD) shows the Return Book System as a single process.
External entities are: Student (provides Book ID and fine payment, receives receipt and error messages),
Library Database (handles book validation and inventory updates), Notification Service (receives return confirmation),
and Shelf Mechanism (receives placement signal).
<img width="488" height="771" alt="Screenshot 2026-05-13 232426" src="https://github.com/user-attachments/assets/91c51af8-65da-43c6-8989-4921e1286f41" />
Level 1 DFD decomposes the system into five sub-processes: 1.0 Validate Book checks if the book belongs to this library; 
2.0 Check Due Date reads borrower records to detect overdue status; 
3.0 Process Fine calculates and collects fines from the student and logs the transaction; 
4.0 Update Inventory writes the updated stock to the Book Inventory store; 
5.0 Send Notification delivers the return confirmation to the Notification Service.
Data stores used: D1 Book Inventory, D2 Borrower Records, D3 Transaction Log.
<img width="662" height="751" alt="Screenshot 2026-05-13 232810" src="https://github.com/user-attachments/assets/dee3bf16-6a6f-48ed-82cd-6d2dd94627e0" />
Level 2 DFD expands Process 3.0 Process Fine into three sub-processes:
3.1 Calculate Fine reads overdue details from Borrower Records and computes the amount;
3.2 Present Fine delivers the fine amount to the Student; 
3.3 Record Payment receives the payment, updates Borrower Records with payment status, and logs the transaction to the Transaction Log.
