
**INTERNAL TRACKING FILE APP**

A powerful tool designed to simplify and automate the flow of paperwork between departments.  
Track every document from creation to final sign-off, assign responsibility, and maintain a complete audit trail—all in one place.



## 🚀 Description

This project is a **web application** for managing and tracking internal documents across departments. It allows you to:

-   📄 Register documents with metadata (code, subject, page count)

-   🏢 Assign and derive documents between different **areas**

-   🔄 Keep a history of **derivations** and **comments**

-   👥 Manage **employees**, **users**, and their **roles**


With this system, organizations can streamline their document flow and ensure full traceability of every record.

## ✨ Features

1.  🗂️ Full CRUD on areas, employees, documents, derivations, and derivation details

2.  🔒 Role-based access control and user authentication

3.  📜 Audit trail for every document movement, with timestamps and user info

4.  🔍 Filters and search by registration number, origin/destination area, and derivation status

5.  🔌 RESTful API ready for integration with other services


## 🗄️ Database Structure

Table

Description

`area`

Defines areas or departments within the organization

`employees`

Stores employees linked to an area (`area_id`)

`document`

Holds document metadata and derivation status (`is_derived`)

`derivations`

Records document transfers between areas (origin and destination)

`derivation_details`

Contains comments and follow-up for each derivation

`users`

Application users, associated with employees and roles

`role`

Role definitions (e.g., admin, viewer) for permission control

**Key relationships:**

-   A document belongs to an employee (`document.employee_id → employees.id`) and a creating area (`document.created_by_area_id → area.id`).

-   Each derivation links a document to two areas (origin and destination).

-   Referential integrity is enforced via foreign keys.


## ⚙️ Installation

1.  **Clone the repo**

    ```bash
    git clone https://github.com/RobertoRuben/internal_tracking_file_app.git
    cd internal_tracking_file_app
    
    ```

2.  **Install PHP dependencies** with Composer

    ```bash
    composer install
    
    ```

3.  **Set up environment**

    ```bash
    cp .env.example .env
    php artisan key:generate
    
    ```

4.  **Configure database** credentials in `.env` (host, port, username, password)

5.  **Run migrations & seeders**

    ```bash
    php artisan migrate --seed
    
    ```

6.  **(Optional) Frontend assets**

    ```bash
    npm install && npm run dev
    
    ```


## 🏁 Usage

-   **Start development server**

    ```bash
    php artisan serve
    
    ```

-   Open your browser at `http://localhost:8000`

-   Log in with the admin user created by the seeder

-   Explore areas, employees, and document modules based on your role


For API details, see the [API documentation](https://chatgpt.com/docs/api.md).

## 🤝 Contributing

Contributions are welcome! Please:

1.  Fork the repository

2.  Create a branch for your feature or fix:

    ```bash
    git checkout -b feature/awesome-new-feature
    
    ```

3.  Submit a pull request with a clear description of changes

4.  Ensure tests pass locally:

    ```bash
    php artisan test
    
    ```


See `CONTRIBUTING.md` for more info.

## 📜 License

This project is licensed under the **MIT License**. See [`LICENSE`](https://chatgpt.com/LICENSE) for details.

## 📬 Contact

-   **Maintainer**: Roberto Rubén – robertoch263@gmail.com

-   **Repo**: [https://github.com/RobertoRuben/internal_tracking_file_app](https://github.com/RobertoRuben/internal_tracking_file_app)

-   **Issues & Wiki**: [https://github.com/RobertoRuben/internal_tracking_file_app](https://github.com/RobertoRuben/internal_tracking_file_app)


Thank you for using and contributing to this project! 🎉
