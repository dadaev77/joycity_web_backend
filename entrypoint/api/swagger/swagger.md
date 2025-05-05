### Документация для обновления swagger

###### 1) Из корня проекта выполнить команду для поиска методов с одинаковым path

```
php entrypoint/api/swagger/find_duplicate_paths.php
```

###### 2) Из корня проекта выполнить команду для добавления operationId для всех 

```
php ./entrypoint/api/swagger/add_operation_ids.php
```

###### 3) Из корня проекта выполнить команду для генерации swagger.json

```
./vendor/bin/openapi -o ./entrypoint/api/swagger/swagger.json ./controllers/
```

###### Login & password

```
'login' => 'joycity',
'password' => 'friflex'
```
