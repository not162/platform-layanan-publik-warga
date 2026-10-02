# Role-Based Access Control (RBAC) Matrix

| Feature / Resource          | Guest | Citizen | Secretary | Treasurer | Security | RT Head | Admin |
|-----------------------------|-------|---------|-----------|-----------|----------|---------|-------|
| Public Announcements        | R     | R       | R         | R         | R        | R       | R,W   |
| Citizen Profile (Self)      | -     | R,U     | R,U       | R,U       | R,U      | R,U     | R,U   |
| Citizen Master Data (All)   | -     | -       | R         | -         | -        | R       | R,W   |
| Request Letters (Self)      | -     | C,R     | C,R       | C,R       | C,R      | C,R     | -     |
| Verify Letters              | -     | -       | U         | -         | -        | -       | U     |
| Approve Letters             | -     | -       | -         | -         | -        | U       | U     |
| Submit Complaints           | C     | C,R     | C,R       | C,R       | C,R      | C,R     | C,R   |
| Manage Complaints (All)     | -     | -       | R,U       | -         | R,U      | R,U     | R,W   |
| Record Finances             | -     | -       | -         | C,R,U     | -        | R       | R     |
| Publish Finance Summary     | -     | -       | -         | C,R       | -        | C,R     | C,R   |
| View Published Finance      | R     | R       | R         | R         | R        | R       | R     |
| View Audit Logs             | -     | -       | -         | -         | -        | R       | R     |

*Notes: C = Create, R = Read, U = Update, W = Write (Create/Update/Delete).*
