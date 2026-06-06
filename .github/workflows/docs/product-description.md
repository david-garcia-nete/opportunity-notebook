# Opportunity Notebook - Product Description

## Overview

Opportunity Notebook is a Laravel web application that helps users identify, evaluate, pursue, and track income opportunities.

The application is designed around a simple idea:

**Opportunities → Actions → Interviews → Income**

Most people manage their careers using email, spreadsheets, notes, memory, and job boards. Opportunity Notebook provides a structured system that helps users make better career and income decisions.

The goal is not to apply to more jobs.

The goal is to make better decisions and consistently take the highest-value actions.

---

## Current Status

### Repository

GitHub:

[git@github.com](mailto:git@github.com):david-garcia-nete/opportunity-notebook.git

Domain:

opportunitynotebook.com

### Technology Stack

* Laravel 13
* Laravel Sail
* Blade
* Tailwind CSS
* MySQL
* Redis
* PHPUnit
* GitHub Actions
* Codecov

### Setup Status

Completed:

* Laravel installed
* Sail configured
* MySQL configured
* Breeze authentication installed
* Tailwind installed
* PHPUnit passing
* GitHub repository created
* Bootstrap committed to main branch

---

## Product Philosophy

The application should function as a personal operating system for career management.

Instead of asking:

"What jobs should I apply for?"

The application asks:

"What is the highest-value action I can take today to improve my career and income?"

The app should encourage deliberate decision-making, networking, follow-ups, project development, and opportunity evaluation.

---

## Initial User

The first user is David Garcia.

The application should initially optimize for David's workflow before considering broader audiences.

Future users may include:

* Job seekers
* Freelancers
* Consultants
* Career changers
* Creative professionals
* Music industry professionals

---

## Core Entities

### Opportunity

A potential source of income.

Examples:

* Job opening
* Contract
* Freelance project
* Business idea
* Music opportunity
* Partnership

Fields:

* title
* company
* type
* status
* score
* notes

---

### Contact

A person connected to an opportunity.

Examples:

* Hiring manager
* Recruiter
* Studio owner
* Business owner
* Networking contact

Fields:

* name
* organization
* email
* phone
* notes

---

### Action

A task that moves an opportunity forward.

Examples:

* Send email
* Apply
* Follow up
* Schedule meeting
* Improve portfolio

Fields:

* title
* due date
* status

---

### Application

A formal application to an opportunity.

Fields:

* opportunity_id
* applied_at
* status

---

### Project

A portfolio project that supports opportunities.

Examples:

* Jam Notebook
* Opportunity Notebook
* Music technology projects
* Open source projects

Fields:

* name
* description
* url

---

## MVP Roadmap

### PR1

Dashboard Shell

Features:

* Replace default Breeze dashboard
* Dashboard cards
* Placeholder metrics
* Tailwind layout

No business logic yet.

---

### PR2

Opportunity Management

Features:

* Opportunity model
* Migration
* CRUD screens
* Opportunity list
* Opportunity details

---

### PR3

Contact Management

Features:

* Contact CRUD
* Contact history
* Follow-up dates

---

### PR4

Action Tracking

Features:

* Actions
* Due dates
* Overdue tracking
* Today's actions

---

### PR5

Applications

Features:

* Application tracking
* Interview tracking
* Status workflow

---

### PR6

Projects

Features:

* Portfolio project tracking
* Associate projects with opportunities

---

### PR7

AI Assistance

Features:

* Opportunity scoring
* Email generation
* Follow-up drafts
* Resume tailoring

AI should assist decision-making, not replace it.

---

## Dashboard Vision

The dashboard should answer:

"What should I do today?"

Example widgets:

* Opportunities
* Active opportunities
* Follow-ups due
* Applications sent this week
* Overdue actions
* Portfolio projects
* Today's focus

The dashboard is the primary value of the application.

---

## Long-Term Vision

Opportunity Notebook becomes a personal career operating system.

It helps users:

* Discover opportunities
* Prioritize opportunities
* Track relationships
* Execute follow-ups
* Build portfolio projects
* Measure progress
* Increase income

The application should always prioritize simplicity, clarity, and action over complexity.
