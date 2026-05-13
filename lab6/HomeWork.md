<img width="1191" height="411" alt="Screenshot 2026-05-13 232837" src="https://github.com/user-attachments/assets/7694f055-5f38-45c2-8857-ca1bbaf9a9b4" />
Context Diagram (Level 0 DFD) shows the Hotel Booking System as a single process. 
External entities are: Guest (submits search criteria, guest details, and payment info; receives available rooms and booking confirmation),
Payment Gateway (processes charge requests and returns results), 
Hotel Admin (provides room data and availability updates), and Notification Service (receives booking notification details).
<img width="1348" height="541" alt="Screenshot 2026-05-13 232907" src="https://github.com/user-attachments/assets/a9fd4bab-fcf4-4acb-8360-05e1aee2b21b" />
Level 1 DFD decomposes the system into five sub-processes: 1.0 Search Rooms queries the Room Catalog using the guest's search criteria;
2.0 Check Availability filters results and returns available rooms to the Guest; 
3.0 Create Booking registers the reservation using guest profile data and saves it to the Bookings store;
4.0 Process Payment sends a charge request to the Payment Gateway and returns the result to Create Booking; 
5.0 Send Notification delivers the booking confirmation to the Guest and Notification Service. Data stores used: D1 Room Catalog, D2 Bookings, D3 Guest Profiles.
<img width="592" height="747" alt="Screenshot 2026-05-13 232936" src="https://github.com/user-attachments/assets/e548df43-7327-494a-aa6f-ffeba7c58011" />
Level 2 DFD expands Process 4.0 Process Payment into three sub-processes: 
4.1 Validate Payment Details reads saved card info from Guest Profiles and verifies the submitted payment data; 
4.2 Charge Amount sends the charge request to the Payment Gateway and receives the result;
4.3 Record Transaction saves the payment record to Payment Records and sends the confirmation back to the Guest.
