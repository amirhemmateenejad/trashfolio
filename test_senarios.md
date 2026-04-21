# Trashfolio – Test Scenarios (TDD Reference)

This document lists the planned test scenarios for Trashfolio.  
It is the reference for writing tests in a **Red → Green → Refactor** cycle.

---

## Legend

- **[F]** Feature test (HTTP/API level)
- **[U]** Unit test (domain logic/helper/service)
- **[P]** Performance-oriented test
- **[S]** Security/Permissions

---

## A. Auth & OTP

### A1. Request OTP

1. **[F] Request OTP with valid phone**
    - Given a valid phone
    - When hitting `POST /api/auth/request-otp`
    - Then:
        - Response is 200 (or 201) with success payload
        - An `otp_codes` record is created with:
            - correct `phone`
            - non-empty `code`
            - `expires_at` in the future

2. **[F] Request OTP with invalid phone format**
    - When phone format is invalid
    - Then:
        - Response is 422 with validation errors
        - No `otp_codes` record is created

3. **[F] Request OTP multiple times (rate limiting)**
    - When same phone requests OTP multiple times in a short period
    - Then:
        - Either:
            - Last OTP overrides previous (previous invalidated)  
              or
            - Request is rejected/limited (depends on final business rule)
        - Rate-limiting behavior is asserted (HTTP status, error message)

4. **[F] Request OTP – ensure `expires_at` is correctly set**
    - `expires_at` should be now + configured TTL (e.g., 5 minutes)
    - Tolerance for small time delta in assertion

5. **[U] OTP generator returns correct format**
    - OTP length (e.g., 4–6 digits)
    - Digits only
    - No leading zeros if that’s a requirement (or explicitly allowed)

---

### A2. Verify OTP

6. **[F] Verify OTP successfully (existing user)**
    - Given an existing user with phone
    - And a valid, not expired, unused OTP code linked to that phone
    - When `POST /api/auth/verify-otp` is called
    - Then:
        - Response is 200
        - User is authenticated (Sanctum token or cookie is issued)
        - `used_at` is set on the OTP record

7. **[F] Verify OTP successfully (new user auto-creation)**
    - Given a phone with no existing user
    - And a valid OTP
    - When verifying OTP
    - Then:
        - A new user is created with that phone
        - Response returns user data + token
        - OTP is marked as used

8. **[F] Verify OTP with wrong code**
    - When code is incorrect
    - Then:
        - Response is 422 or 400 (depending on design)
        - No user is created
        - OTP is not marked as used

9. **[F] Verify OTP with expired code**
    - Given an OTP whose `expires_at` is in the past
    - When verifying
    - Then:
        - Response is 422 or 400 "expired code"
        - `used_at` remains null

10. **[F] Verify OTP with already-used code**
    - Given an OTP with `used_at` not null
    - When verifying
    - Then:
        - Response is error (invalid OTP)
        - No side effects

11. **[F] Verify OTP – phone/code mismatch**
    - Code belongs to another phone
    - Verifying with mismatched phone returns error

12. **[U] OTP verification logic**
    - A pure function or service:
        - Accepts `phone`, `code`
        - Returns:
            - Success (user) or
            - Typed error (`invalid`, `expired`, `already_used`)

---

### A3. Profile & Me

13. **[F] Get current authenticated user (`/api/auth/me`)**
    - Authenticated request returns user data
    - Unauthenticated returns 401

14. **[F] Update profile (name, etc.)**
    - Authenticated user can `PATCH /api/auth/profile` with `name`
    - Validation:
        - Name required, min/max length
    - After update, `name` is stored and returned

15. **[S] Access profile without auth**
    - Unauthenticated request to profile endpoints returns 401

---

### A4. Logout & Token Handling

16. **[F] Logout**
    - With Sanctum, `POST /api/auth/logout`:
        - Revokes current token
        - Subsequent requests are unauthorized

17. **[S] Using revoked token**
    - After logout, old token must not access protected routes

---

## B. Projects

### B1. Create Project

18. **[F] Create project with valid data**
    - Fields: `name`, optional `description`
    - Project is created for authenticated user
    - Response contains project data

19. **[F] Validation errors**
    - Empty `name` or too long → 422

20. **[S] Unauthenticated create**
    - Unauthenticated request → 401

---

### B2. List & View Projects

21. **[F] List projects (pagination)**
    - `/api/projects`
    - Returns paginated list:
        - `data`, `meta`, `links`
        - Default `per_page` is enforced

22. **[S] Only own projects visible**
    - User A cannot see User B’s projects

23. **[F] View single project**
    - `/api/projects/{id}` returns 404 if not owned
    - Returns project details if owned

---

### B3. Update & Delete Projects

24. **[F] Update project**
    - Owner can update name/description
    - Response reflects changes

25. **[S] Update project not owned by user**
    - Returns 403 or 404 (depending on design)

26. **[F] Delete project**
    - Owner can delete
    - Cascading behavior for `folders` & `snippets` is correct:
        - Either deleted or reassigned based on final rules

27. **[S] Delete project not owned by user**
    - Returns 403 or 404

---

## C. Folders

### C1. Create Folder

28. **[F] Create folder in project**
    - Fields: `name`, `project_id`, optional `parent_id`
    - Belongs to authorized user’s project

29. **[F] Validation**
    - `name` required
    - `parent_id` must belong to the same project (if provided)

30. **[S] Create folder in someone else’s project**
    - Returns 403/404

---

### C2. Hierarchy & Loops

31. **[F] Prevent folder loop in `parent_id`**
    - Trying to set folder’s parent to itself → validation error
    - Trying to set parent to a descendant → validation error

32. **[U] Tree validation**
    - Unit test tree-traversal or loop-detection helper

---

### C3. Update & Delete Folders

33. **[F] Update folder name**
    - Owner can rename folder

34. **[F] Move folder (change `parent_id`)**
    - Ensure loops still prevented
    - Ensure folder stays within the same project

35. **[F] Delete folder**
    - Confirm cascade:
        - Children folders
        - Snippets (or reassign, depending on policy)

36. **[S] Operations on folder of another user**
    - All return 403/404

---

## D. Snippets

### D1. Create Snippet

37. **[F] Create snippet directly under a project (root)**
    - `project_id` given, `folder_id` null
    - Snippet belongs to project and user

38. **[F] Create snippet inside folder**
    - `folder_id` provided
    - Folder must belong to the same project & user

39. **[F] Validation**
    - `title` required
    - `code` required
    - `language` format (set of allowed values or free string, depending on design)

40. **[F] Attach tags on create by `tag_ids`**
    - `tag_ids` array sent
    - Only tags belonging to the current user are allowed
    - If any tag belongs to another user → request fails (403 or 422)
    - If tag does not exist → 422
    - After creation:
        - Pivot `snippet_tag` contains correct entries
        - No duplicates

41. **[F] Attach tags on create by `tag_names` (on-the-fly creation)**
    - `tag_names` array sent
    - Non-existing names → new tags created for user
    - Existing names (for user) reused
    - All attached to the new snippet
    - No duplicate tags created for same name

---

### D2. Update Snippet

42. **[F] Update snippet basic fields**
    - Owner can update `title`, `description`, `code`, `language`

43. **[F] Update snippet folder (move between folders)**
    - New folder must be in the same project
    - Folder must belong to the user
    - Setting `folder_id` to null moves snippet to project root

44. **[F] Sync tags by `tag_ids`**
    - Updating snippet with `tag_ids`:
        - Old tags not in the list are detached
        - New tags in the list are attached
        - No duplicate pivot records

45. **[F] Prevent attaching tags belonging to other users**
    - If any `tag_id` belongs to another user → entire update fails

46. **[F] Update snippet with `tag_names`**
    - Creates missing tags
    - Reuses existing user tags where possible
    - Sync behavior remains consistent

47. **[S] Update snippet of another user**
    - Forbidden (403/404)

---

### D3. Delete Snippet

48. **[F] Delete snippet**
    - Owner deletes snippet
    - Pivot `snippet_tag` entries for that snippet are removed

49. **[S] Delete snippet of another user**
    - Forbidden

---

### D4. List & View Snippets

50. **[F] List snippets in project**
    - `/api/projects/{project}/snippets`
    - Includes root-level and/or nested behavior depending on design
    - Pagination

51. **[F] Filter snippets by folder**
    - Query param `folder_id`
    - Only snippets in the folder returned

52. **[F] Filter by language**
    - `language` param filters snippets

53. **[F] Include tags in snippet response**
    - Eager-loaded relationships

54. **[S] Access snippet of other user**
    - 403/404

---

## E. Tags

### E1. Create & List Tags

55. **[F] Create tag**
    - Input: `name`, optional `color`
    - `slug` auto-generated from `name`
    - Unique per user (`slug`)

56. **[F] Prevent duplicate tag name per user**
    - Same name for same user → validation error

57. **[F] Use same tag name for different users**
    - User A and User B can both have tag `"bug"`; separate records

58. **[F] List tags**
    - `/api/tags`
    - Only tags of current user returned

---

### E2. Update & Delete Tags

59. **[F] Update tag**
    - Change `name` → `slug` updated or not, depending on chosen rule
    - Change `color`

60. **[S] Update tag of another user**
    - Forbidden

61. **[F] Delete tag**
    - Tag removed
    - Pivot records (`snippet_tag`) for that tag are removed

62. **[S] Delete tag of another user**
    - Forbidden

---

### E3. Attach/Detach Tag to/from Snippet

63. **[F] Attach existing tag to snippet**
    - User must own both snippet and tag
    - After attach, pivot exists and no duplicates

64. **[F] Detach tag from snippet**
    - Removes pivot entry

65. **[S] Attach foreign user’s tag**
    - Forbidden

66. **[S] Attach tag to snippet not owned by user**
    - Forbidden

---

## F. Search

### F1. Full-Text Search on Snippets

67. **[F] Search by title/description**
    - Endpoint returns snippets matching query
    - Full-text index used for performance

68. **[F] No access leakage in search**
    - User A cannot discover snippets of User B via search

69. **[F] Pagination on search results**
    - Standard pagination fields present
    - `per_page` limit applied

---

### F2. Search Filters

70. **[F] Search with project filter**
    - `project_id` param limits search to that project

71. **[F] Search with tag filter**
    - `tag_ids[]` param returns snippets tagged accordingly
    - Intersection behavior defined (AND vs OR) and tested

72. **[F] Search with language filter**
    - `language` param reduces result set

---

## G. Permissions & Multi-Tenancy

73. **[S] User isolation across entire API**
    - Any resource belonging to another user:
        - Not visible or accessible
        - API returns 403 or 404 consistently

74. **[S] Cross-project folder/snippet/tag mismatch**
    - Cannot:
        - Attach snippet from project A to folder in project B
        - Attach tag of user A to snippet of user B

75. **[S] Defensive checks on all ID-based endpoints**
    - IDs in URL must always be validated against authenticated user

---

## H. API Contract & Validation

76. **[F] Consistent error format**
    - Validation errors return same JSON structure everywhere
    - HTTP status code 422 for validation

77. **[F] Missing/invalid JSON body**
    - Proper error response

78. **[F] Type validation**
    - `tag_ids` must be array of integers
    - `color` must be valid hex (if enforced)
    - `language` string length constraints

---

## I. Database & Factories

79. **[U] Model factories exist and work**
    - `User`, `Project`, `Folder`, `Snippet`, `Tag`

80. **[U] Cascading behavior**
    - Deleting project deletes or handles folders/snippets according to defined constraints
    - Deleting user clears dependencies or is prevented (depending on rules)

---

## J. Performance & Indexes

81. **[P] N+1 on listing snippets**
    - When listing snippets with tags
    - Ensure tags are eager-loaded
    - Count DB queries and assert below a threshold

82. **[P] N+1 on projects/folders listing**
    - Similar checks for project tree

83. **[P] Pagination enforced**
    - Large dataset → listing endpoints still respond quickly
    - `per_page` cannot exceed maximum allowed

84. **[P] Full-text index exists**
    - Check migration for index on `title` + `description`
    - Optional: DB-level assertion if feasible

---

## K. Edge Cases & Misc

85. **[F] Snippet with `folder_id = null` (root snippets)**
    - Correctly returned in project snippet listing
    - No issues when moving to/from folders

86. **[F] Moving folder with many nested items**
    - Tree remains consistent
    - No loops introduced

87. **[F] Tag deletion when attached to many snippets**
    - All pivot rows removed
    - Snippets remain intact

---

## Notes

- This list is intentionally exhaustive for TDD.
- Not all tests must be implemented at once, but all new behavior should refer back to this document.
- Whenever a new feature is added or changed, **update this file first**, then write/adjust tests accordingly.