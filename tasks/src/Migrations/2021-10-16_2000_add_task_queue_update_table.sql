create table if not exists task_queue_update (
    task_id bigint unsigned not null primary key,
    num_tries tinyint unsigned not null,
    last_try timestamp null default null
) character set utf8mb4 collate utf8mb4_general_ci;
