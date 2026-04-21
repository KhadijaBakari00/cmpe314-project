# Lab 3 – Use Case Diagram (Hotel Booking System)

---

## Course & Team Information

**Course:** CMPE314 – Software Engineering  
**Class Group:** Group 3  

**Team Members**

| Student ID | Name 
|------------|------
| 22201315 | Khadija Bakari Isa 
| 22100923 | Bandar Mohamed 
| 22204973 | Aiya Bitabarova 

---

## Project Description

A hotel booking system allows customers to search, view, and reserve hotel rooms online in a convenient and efficient way. Customers can browse available hotels and rooms based on different criteria such as location, price range, room type, and availability dates. They are also able to search for specific rooms and view detailed information including price, amenities, photos, and descriptions.

Registered customers can log in to their accounts to access additional features. They can select rooms and add them to a booking cart. During the checkout process, customers provide personal information, select booking dates, and enter payment details. The system integrates with an external payment gateway to securely process payments. Once the payment is successful, the system generates and sends a booking confirmation to the customer via email.

Customers can also manage their bookings by viewing booking history, modifying reservations, or canceling bookings if necessary.

Administrators are responsible for managing the hotel booking system. They can add new hotels and rooms, update room information such as price, availability, and amenities, and remove rooms that are no longer available. Administrators can also monitor all bookings and update booking statuses (e.g., confirmed, cancelled, completed).

Additionally, the system automatically sends notifications to customers when there are any changes to their booking status.

---<img width="1061" height="675" alt="Screenshot 2026-04-22 020828" src="https://github.com/user-attachments/assets/1ec28c93-58b1-4170-9f7b-ad61a984d87e" />



## Actors & Use Cases

| Actor | Description | Use Cases |
|-------|------------|-----------|
| Customer | A user who can browse hotels and rooms without registering. | Browse Hotels, Search Rooms, View Room Details |
| Registered Customer | Inherits from Customer; can make bookings and manage their account. | Login, Add to Booking Cart, Place Booking |
| Administrator | Manages the hotels, rooms, and bookings. | Manage Hotels, Manage Rooms, Update Booking Status |
| Payment Gateway | External system responsible for processing payments. | Process Payment |

---


## Use Case Descriptions

- **Browse Hotels:** Customers can view available hotels.  
- **Search Rooms:** Customers can search for rooms based on criteria such as location, price, and type.  
- **View Room Details:** Customers see detailed info for each room including amenities and photos.  
- **Login:** Registered Customers log in to access additional features.  
- **Add to Booking Cart:** Registered Customers can select rooms to add to their booking cart.  
- **Place Booking:** Registered Customers confirm room bookings.  
- **Checkout:** Handles the final booking confirmation process (included in Place Booking).  
- **Process Payment:** Payment Gateway securely processes payment (included in Checkout).  
- **Manage Hotels:** Administrators can add or update hotel information.  
- **Manage Rooms:** Administrators manage room details like availability, price, and amenities.  
- **Update Booking Status:** Administrators change booking status (confirmed, cancelled, completed).

**Include Relationships:**  
- Place Booking → *include* → Checkout  
- Checkout → *include* → Process Payment  


