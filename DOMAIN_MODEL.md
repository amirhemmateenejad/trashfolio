# Domain Model

Trashfolio is built around a simple hierarchical structure.

Main entities:

Users

Projects

Folders

Snippets

Tags

---
# Relationships

User → Projects

Project → Folders

Folder → Subfolders

Project / Folder → Snippets

Snippet → Tags

---

## Projects
Projects group related snippets.

Each project belongs to a user.

Fields:
- id
- user_id
- name
- description

---

## Folders
Folders organize snippets inside projects.

Folders can be nested.

Fields:
- id
- project_id
- parent_id
- name

Rules:
- parent folder must belong to same project
- folder loops are not allowed

---
## Snippets

Snippets store code.

A snippet can belong to:
- a project root
- a folder

Fields:
- id
- project_id
- folder_id
- title
- content

---

## Tags
Tags provide classification.

Fields:

- id
- user_id
- name
- slug
- color

Tags are unique per user.

---
## Pivot Tables

snippet_tag

Fields:

- snippet_id
- tag_id

--- 
## Ownership Resolution
Snippets do not directly store user_id.

Ownership is resolved through the chain:

Snippet → Project → User

or

Snippet → Folder → Project → User

This ensures consistent multi‑tenancy and access control.

---

## Soft Deletes

Snippets use soft deletes.

Deleted snippets are moved to a trash system and can be restored before permanent deletion.