The activity diagram describes the workflow of a Hotel Booking System when a customer makes a room reservation. 
First, the customer enters booking details, and the system checks room availability in the database. 
If no rooms are available, the process stops and an error message is shown. 
If rooms are available, the customer selects a room and provides guest information. 
The system then calculates the total booking cost and processes the payment. 
If the payment fails, the booking is canceled. 
If the payment is successful, the system performs several actions in parallel: updating the booking database, sending a confirmation email, and reserving the selected room. 
Finally, the booking process is completed.
<img width="553" height="755" alt="Activity2" src="https://github.com/user-attachments/assets/135636b5-69f0-4349-81f6-592706654555" />
