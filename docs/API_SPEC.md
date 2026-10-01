# API Specification

## Base URL
`/api/v1`

## Envelope Format
All responses must follow the standard envelope pattern:
```json
{
  "data": { ... },
  "meta": {
    "version": 1,
    "updated_at": "2026-10-01T00:00:00Z"
  },
  "message": "OK"
}
```

## Endpoints

### Public Endpoints
- `GET /public/announcements`: Retrieve paginated public announcements.
- `GET /public/events`: Retrieve paginated public events.
- `GET /public/finance/summary`: Retrieve published financial summaries.

### Authenticated Citizen Endpoints
- `GET /me`: Retrieve the authenticated user's profile and linked citizen record.
- `GET /letters`: Retrieve the citizen's own letter requests.
- `POST /letters`: Create a new letter request.
- `GET /letters/{id}`: View details of a specific letter request.
- `GET /complaints`: Retrieve the citizen's submitted complaints.
- `POST /complaints`: Submit a new complaint.

### Admin / Staff Endpoints
- `GET /admin/citizens`: Retrieve paginated list of citizens (Admin only).
- `POST /admin/citizens`: Create a citizen record (Admin only).
- `PUT /admin/citizens/{id}`: Update a citizen record (Admin only, requires `version` for optimistic locking).
- `DELETE /admin/citizens/{id}`: Delete a citizen record (Admin only).
- `GET /admin/letters`: Retrieve all letter requests for processing (Secretary, RT Head).
- `PATCH /admin/letters/{id}/status`: Update letter request status (Secretary, RT Head).

## Polling and Concurrency
- `GET` endpoints for near-real-time polling should support `ETag` or `If-Modified-Since` headers to return `304 Not Modified` when unchanged.
- `PUT/PATCH` requests on concurrent entities must include a `version` field. The server will reject the update with a `409 Conflict` if the provided version does not match the current database version.
