# Trashfolio — Development TODO

Reference: TEST_SCENARIOS.md for all test IDs.  
Branch: `claude/fervent-rubin-26g9k1`

---

## Phase 1 — Backend MVP ✅ FULLY COMPLETE

### Authentication (A)
- [x] OTP request endpoint (`POST /api/auth/login`)
- [x] OTP verify endpoint (`POST /api/auth/verify`) — creates user on first login
- [x] Token refresh (`POST /api/auth/refresh`)
- [x] Get current user (`GET /api/user`)
- [x] Update profile (`PUT /api/user`)
- [x] Logout / token revocation
- [x] `OtpCode` model with `expires_at`, `used_at`
- [x] `IranianMobile` validation rule
- [x] OTP service with pluggable SMS drivers (Ghasedak, Melipayamak)
- [x] Scheduled cleanup: expired OTPs, expired tokens
- [x] Feature tests: `RequestOtpTest`, `VerifyOtpTest`, `ProfileTest`

### Projects (B)
- [x] CRUD: `POST /api/projects`, `GET /api/projects`, `GET /api/projects/{id}`, `PUT /api/projects/{id}`, `DELETE /api/projects/{id}`
- [x] Soft delete with cascade to folders and snippets
- [x] Restore cascades to folders and snippets
- [x] `ProjectPolicy` — ownership enforced
- [x] `ProjectFactory`
- [x] Feature tests: `ProjectCrudTest` (B18–B27)

### Folders (C)
- [x] CRUD: `POST /api/folders`, `GET /api/folders/{id}`, `PUT /api/folders/{id}`, `DELETE /api/folders/{id}`
- [x] `parent_id` nesting — must belong to same project
- [x] Cross-project parent validation
- [x] Soft delete with cascade to children and snippets
- [x] `FolderPolicy` — ownership via project chain, trashed project awareness
- [x] `FolderFactory`
- [x] Feature tests: `FolderCrudTest` (C28–C36)
- [x] Loop/cycle detection in `parent_id` (C31–C32 from TEST_SCENARIOS)
- [x] Move folder (change `parent_id`) with loop prevention (C34)

### Snippets (D)
- [x] CRUD: `POST /api/snippets`, `GET /api/snippets`, `GET /api/snippets/{id}`, `PUT /api/snippets/{id}`, `DELETE /api/snippets/{id}`
- [x] `language` field
- [x] `tag_ids` — attach existing user-owned tags on create/update
- [x] `tag_names` — auto-create and attach tags by name on create/update
- [x] Tag sync (`sync()`) on update — detaches removed tags
- [x] Ownership enforced via project chain
- [x] Pivot cleanup on soft-delete
- [x] `SnippetPolicy` — full set including `restore`/`forceDelete` with trashed parent awareness
- [x] `SnippetFactory`
- [x] Feature tests: `SnippetCrudTest` (D37–D54)
- [x] Filter snippets by folder (`GET /api/snippets?folder_id=`) (D51)
- [x] Filter snippets by language (`GET /api/snippets?language=`) (D52)
- [x] Nested snippet listing per project (`GET /api/projects/{project}/snippets`) (D50 full spec)

### Tags (E)
- [x] `user_id` and `slug` columns — per-user unique by slug
- [x] CRUD: `GET /api/tags`, `POST /api/tags`, `PUT /api/tags/{id}`, `DELETE /api/tags/{id}`
- [x] Attach/detach: `POST /api/snippets/{snippet}/tags/{tag}`, `DELETE /api/snippets/{snippet}/tags/{tag}`
- [x] Tag deletion removes pivot records
- [x] `TagPolicy` — ownership enforced
- [x] `TagFactory`
- [x] Feature tests: `TagCrudTest` (E55–E66)

### Search (F)
- [x] `GET /api/search` with `q`, `project_id`, `folder_id`, `tag_ids[]`, `language`, `per_page`
- [x] `Snippet` model: `use Searchable`, `toSearchableArray()` includes `user_id`, `language`, `tags[]`
- [x] Meilisearch index settings: filterable/searchable/sortable attributes in `scout.php`
- [x] Dual-layer security: Meilisearch `filter` (production) + SQL `whereHas` (always)
- [x] `tag_ids` resolves only caller-owned tags; foreign tag IDs yield zero results
- [x] `shouldBeSearchable()` excludes soft-deleted snippets
- [x] Feature tests: `SearchTest` (F67–F72 + extras)
- [x] Autocomplete endpoint (`GET /api/autocomplete?q=&types[]=&limit=`)

### Trash System
- [x] `GET /api/trash` — paginated list of all user's soft-deleted items (projects, folders, snippets)
- [x] Each item includes `type`, `id`, `title`, `deleted_at`
- [x] Items whose parent project is also trashed are still listed correctly
- [x] `POST /api/trash/{type}/{id}/restore` — restore single item with ownership check
- [x] `DELETE /api/trash/{type}/{id}` — permanently delete single item with ownership check
- [x] `DELETE /api/trash` — permanently empty all user's trash
- [x] Feature tests: `TrashTest` (28 tests)

### Infrastructure
- [x] SQLite in-memory for tests (`phpunit.xml`)
- [x] `SCOUT_DRIVER=collection` for tests (no live Meilisearch required)
- [x] `Pest.php` — global `TestCase` + `RefreshDatabase` for all feature tests
- [x] Factories for all models: `User`, `Project`, `Folder`, `Snippet`, `Tag`
- [x] Scheduled commands: `CleanExpiredOtpsCommand`, `CleanExpiredTokensCommand`, `CleanTrashCommand`

---

## Phase 2 — Backend Hardening (Next)

### Snippets — Missing Filters
- [x] `GET /api/snippets?folder_id=` filter (D51)
- [x] `GET /api/snippets?language=` filter (D52)
- [x] `GET /api/projects/{project}/snippets` — project-scoped snippet listing (D50)

### Folder Tree
- [x] Loop/cycle detection when setting `parent_id` (C31–C32)
- [x] Move folder between parents — validation that target is in same project (C34)

### API Contract & Validation (H)
- [x] Consistent JSON error response format — Form Requests + Resources across all endpoints (H76)
- [x] `per_page` max enforcement on all listing endpoints (J83)
- [x] Type validation: `tag_ids` array of integers, `color` valid hex via `HexColor` rule (H78)
- [x] Form Requests for all controllers (validation separated from controller logic)
- [x] API Resources for all responses (no raw response()->json())
- [x] Custom Rules: `HexColor`, `UniqueTagName`, `BelongsToProject`, `NoCyclicParent`
- [x] `TagService` extracted (resolveIds, resolveNamesForUser)

### Performance (J)
- [ ] Eager-load audit: assert no N+1 on snippet listing with tags (J81)
- [ ] Eager-load audit: assert no N+1 on folder tree listing (J82)
- [ ] Full-text index migration verification (J84)

### Multi-Tenancy Audit (G)
- [ ] End-to-end cross-user isolation test across all resources (G73)
- [ ] Cross-project folder/snippet/tag mismatch tests (G74)
- [ ] Defensive ID validation test sweep (G75)

### Edge Cases (K)
- [ ] Root snippets (`folder_id = null`) list correctly (K85)
- [ ] Moving folder with many nested children (K86)
- [ ] Tag deletion when attached to many snippets (K87)

---

## Phase 3 — Frontend (Future)

- [ ] Vue 3 SPA setup with Vite
- [ ] Pinia store: auth, projects, snippets, tags
- [ ] OTP login flow
- [ ] Project sidebar
- [ ] Folder tree navigation
- [ ] Snippet editor (Monaco)
- [ ] Tag management UI
- [ ] Search interface
- [ ] Trash manager UI
- [ ] Dark mode

---

## Phase 4 — Future Features (ROADMAP)

- [ ] Snippet sharing (public links)
- [ ] Collaboration
- [ ] Import/export
- [ ] Browser extension
- [ ] CLI tool
