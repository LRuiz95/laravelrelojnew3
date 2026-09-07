# ADR-002: Composite PK Strategy
**Decisión**: A
**Justificación**: id surrogate autoincrement + unique constraint en PK compuesta. Eloquent relations funcionan nativamente con id; PK compuesta como constraint evita duplicados Firebird.