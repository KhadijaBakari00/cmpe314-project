Story: Hotel Booking System

A guest uses the hotel booking system to search for available rooms and make a reservation. 
The system checks availability, creates a booking, and processes the payment.
If the payment is successful, the system confirms the booking and sends a notification to the guest. 
If rooms are unavailable or payment fails, the booking is cancelled and the guest is informed.

<img width="656" height="486" alt="Screenshot 2026-04-30 002155" src="https://github.com/user-attachments/assets/ac5ad4fe-a736-4acd-8d03-c22e68de589d" />

Normal Flow (Happy Path)

In the hotel booking system, the user searches for available rooms and selects a suitable option.
The system checks availability through the database and confirms the booking request.
Once confirmed, the reservation is saved in the system, payment is processed if required, and a confirmation message is sent to the user via the notification service. 
Finally, the booking is successfully completed and stored in the system.

lternative Flow (No Availability / Payment Issue)

If no rooms are available for the selected dates, the system rejects the booking request and notifies the user.
Alternatively, if payment fails during the booking process, the reservation is not confirmed and the system cancels the transaction, prompting the user to try again or choose another room.
