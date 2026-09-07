# ADR-001: Employee/Teacher/Attendance Unification
**Decisión**: A
**Justificación**: Un solo modelo Employee con type enum simplifica FKs, pivotes, scopes y dashboard KPIs. Los campos nullable (numero_empleado, clave_profesor, etc.) solo se usan cuando type=admin/teacher.