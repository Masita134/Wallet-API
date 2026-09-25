# WAL-021 — Matriz de cobertura funcional

## Objetivo

Documentar la relación entre los flujos funcionales críticos de la API y los
tests que verifican su comportamiento.

| Flujo | Tests de cobertura |
|---|---|
| Registro de usuario | `AuthTest::test_a_user_can_register` |
| Login | `AuthTest::test_a_user_can_login` |
| Acceso privado sin autenticación | `AuthTest::test_an_unauthenticated_user_cannot_view_his_profile` |
| Perfil propio | `ProfileTest::test_authenticated_user_can_view_only_his_own_profile` |
| Actualización de perfil válida/inválida | `ProfileTest::test_authenticated_user_can_update_his_own_profile`, `ProfileTest::test_profile_update_rejects_invalid_data` |
| Depósito válido | `DepositTest::test_authenticated_user_can_make_a_deposit` |
| Depósito inválido | `DepositTest::test_deposit_rejects_zero_amount_without_changing_balance_or_movements`, `DepositTest::test_deposit_rejects_negative_amount_without_changing_balance_or_movements`, `DepositTest::test_deposit_rejects_non_numeric_amount_without_changing_balance_or_movements` |
| Consulta de cuenta y saldo | `AccountDataTest::test_authenticated_user_can_view_account_data` |
| Aislamiento de cuentas | `AccountDataTest::test_user_cannot_access_another_users_account` |
| Consulta de movimientos propios | `MovementTest::test_authenticated_user_can_consult_own_movements` |
| Aislamiento de movimientos | `MovementTest::test_movements_are_limited_to_authenticated_users_account` |
| Transferencia válida | `TransferTest::test_authenticated_user_can_transfer_money` |
| Transferencia con saldo insuficiente y sin cambios parciales | `TransferTest::test_transfer_rejects_insufficient_balance` |
| Aislamiento de CBUs guardados | `SavedAccountTest::test_saved_accounts_are_isolated_between_users` |
| Guardado de CBU de tercero | `SavedAccountTest::test_authenticated_user_can_save_a_third_party_account` |
| Validaciones de CBU guardado | `SavedAccountTest::test_cannot_save_a_non_existing_cbu`, `SavedAccountTest::test_cannot_save_own_account`, `SavedAccountTest::test_cannot_save_the_same_account_twice` |
| Protección de lista de otro usuario | `SavedAccountTest::test_cannot_save_account_for_another_user` |
| Simulación de plazo fijo válida y saldo intacto | `FixedTermSimulationTest::test_simulacion_exitosa_y_saldo_intacto` |
| Simulación de plazo fijo inválida | `FixedTermSimulationTest::test_rechaza_datos_invalidos_con_422` |
| Plazo fijo sin autenticación | `FixedTermSimulationTest::test_usuario_no_autenticado_recibe_401` |
| Permisos usuario/admin | `AdminAuthorizationTest::test_regular_user_cannot_access_admin_route`, `AdminAuthorizationTest::test_admin_can_access_admin_route` |
| Paginación de movimientos | `MovementTest::test_movements_are_paginated_with_fifteen_items_by_default`, `MovementTest::test_user_can_choose_number_of_movements_per_page` |
| Ordenamiento de movimientos | `MovementTest::test_default_order_is_descending_by_date`, `MovementTest::test_user_can_order_movements_ascending_by_date` |
| Paginación administrativa | `AdminMovementTest::test_admin_puede_configurar_paginacion` |
| Ordenamiento administrativo | `AdminMovementTest::test_admin_puede_ordenar_movimientos` |
| Base de datos aislada por test | Tests funcionales que utilizan `RefreshDatabase` |
| Ejecución independiente del orden | Suite ejecutada exitosamente con `php artisan test --order-by=random` |

## Comandos de verificación

Ejecutar la suite completa:

```bash
php artisan test
```
Ejecutar la suite en orden aleatorio:
```bash
php artisan test --order-by=random
```
Los tests funcionales utilizan RefreshDatabase, por lo que cada test trabaja
sobre una base de datos de prueba reiniciada.

## Resultado de la auditoría

La cobertura requerida por WAL-021 fue revisada evitando agregar escenarios
duplicados cuando ya existía una prueba suficiente.

Se agregaron específicamente los escenarios que faltaban para:

verificar que una transferencia rechazada por saldo insuficiente no produzca cambios parciales;
verificar el aislamiento de la cuenta entre usuarios;
verificar el aislamiento de las cuentas CBU guardadas entre usuarios;
corregir tests que estaban ubicados accidentalmente dentro de AuthController y mantenerlos en AuthTest.