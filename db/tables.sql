begin;

-- в случае перезаписи БД
drop table if exists tasks cascade;

create table tasks (
    id bigserial primary key,
    author text not null,
    email text not null,
    content text not null,
    is_done boolean not null default false,
    is_updated boolean not null default false,
    client_ip text default null,
    created_at timestamp not null default now(),
    updated_at timestamp default null
);

create index idx_tasks_author on tasks(author);
create index idx_tasks_email on tasks(email);
create index idx_tasks_client_ip on tasks(client_ip);
create index idx_tasks_created_at on tasks(created_at);
create index idx_tasks_is_done on tasks(is_done);

commit;
