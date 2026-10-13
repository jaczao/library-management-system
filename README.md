# BiblioStudio: Library Management System

A web application for a library with several branches. A PostgreSQL database enforces the lending rules itself, through triggers, stored procedures and views; a PHP front end serves readers and librarians.

Individual lab project for the Databases module, BSc Computer Science, University of Milan, February 2025. The interface and code comments are in Italian.

Demo video: https://youtu.be/6B4tKmkzv98

## Rules enforced in the database

| Rule | Enforced by |
|---|---|
| A reader with 5 or more late returns cannot borrow | `BEFORE INSERT` trigger on loans |
| Base readers can hold at most 3 books at once, premium readers 5 | same trigger |
| Only an available copy can be lent; availability updates on every loan and return | same trigger, plus `AFTER INSERT` and `AFTER UPDATE` triggers |
| A late return adds one to the reader's late-return count | `AFTER UPDATE` trigger on loans |
| A loan can be extended only if not yet overdue, and only to a later date | procedure `proroga_prestito` |
| A reader can ask for a preferred branch; if it has no free copy, the error lists the branches that do | procedure `assegna_prestito` |
| Librarians can reset a reader's late-return count | procedure `azzera_ritardi` |
| Per-branch totals of copies, distinct ISBNs and active loans | materialised view `sede_statistiche`, refreshed by statement-level triggers |
| Per-branch report of overdue loans and the readers holding them | view `sede_ritardi` |

## Web application

- Separate logins for readers and librarians; passwords stored as bcrypt hashes.
- **Readers** search books (by title or ISBN) and authors, view their details, borrow a book (optionally at a chosen branch), see their loans and edit their profile.
- **Librarians** manage readers, books, authors and branches, record returns, extend loans, and view the branch statistics and overdue report.

The schema has eight tables: branches, authors, books, book–author links, copies, readers, librarians and loans. `Relazione.pdf` (in Italian) has the ER diagrams, the relational schema and a user manual; `BDLAB-progetto-2023_2024.pdf` is the brief.

## Running locally

Needs PostgreSQL (the dump was made with 17.2) and PHP with the `pdo_pgsql` extension.

```bash
# Drops and recreates the "biblioteca" database, with sample data
psql -U postgres -d postgres -f dump.sql

# Edit config.php if your PostgreSQL user or password is not postgres/postgres
php -S 127.0.0.1:9000
```

Open http://127.0.0.1:9000. Sample accounts, both with password `1234`:

| Role | Email |
|---|---|
| Reader | marco.rossi@email.com |
| Librarian | luca.verdi@email.com |

The SQL sources are in `sql/`: `schema.sql`, `functions.sql`, `reports.sql` and `data.sql`.
