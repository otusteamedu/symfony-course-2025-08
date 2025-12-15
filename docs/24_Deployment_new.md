# Развёртывание приложения

## Устанавливаем и настраиваем Gitlab и Gitlab Runner

1. В отдельной директории создаём файл `docker-compose.yml`
    ```yaml
    services:
      gitlab:
        image: gitlab/gitlab-ee:latest
        container_name: gitlab
        restart: always
        hostname: 'gitlab.local'
        environment:
          GITLAB_OMNIBUS_CONFIG: |
            external_url 'http://gitlab.local:7778'
            gitlab_rails['gitlab_shell_ssh_port'] = 9022
        ports:
          - '7778:7778'
          - '9022:22'
        volumes:
          - gitlab_config:/etc/gitlab
          - gitlab_logs:/var/log/gitlab
          - gitlab_data:/var/opt/gitlab
        shm_size: '256m'
    
      gitlab-runner:
        image: gitlab/gitlab-runner:latest
        container_name: gitlab-runner
        restart: always
    
    volumes:
      gitlab_config:
      gitlab_logs:
      gitlab_data:
    ```
2. Прописываем адрес `gitlab` в `/etc/hosts`
3. Запускаем контейнеры командой `docker compose up -d`
4. Дождаться запуска Gitlab
5. Зайти в контейнер Gitlab командой `docker exec -it gitlab sh`
6. В контейнере открыть консоль Rails командой `gitlab-rails console -e production`
7. В консоли ввести команды (`PASSWORD` – требуемый пароль):
    ```
    user = User.where(id: 1).first
    user.password = PASSWORD
    user.password_confirmation = PASSWORD
    user.save
    exit
    ```
8. В хост-системе создаём ключи для работы с Gitlab командой `ssh-keygen -t rsa -b 2048`, указываем путь до своей
   директории `.ssh` и своё имя файла
9. В хост-системе выполняем команду (`PATH` – указанный на предыдущем шаге путь)
    ```shell
    sudo chmod 600 PATH
    eval $(ssh-agent -s)
    ssh-add PATH
    ```
10. Заходим в браузере по адресу `http://localhost:7778`
    1. логинимся с логином `root` и указанным паролем
    2. Создаём публичную группу и публичный репозиторий в ней
    3. Заходим в редактирование профиля и добавляем SSH-ключ
11. В хост-системе клонируем репозиторий, помещаем в него код проекта и пушим обратно в гитлаб
12. Заходим в Gitlab-репозиторий в браузере, переходим на вкладку Settings -> CI/CD -> Runners, копируем команду
    для регистрации runner'а без префикса `sudo`
13. Входим в контейнер командой `docker exec -it gitlab-runner sh`
14. Выполняем команду регистрации, соглашаемся со всеми значениями по умолчанию, в качестве `executor` выбираем `shell`
15. Проверяем в интерфейсе, что runner появился

## Настраиваем виртуальную машину

1. Заходим в виртуальную машину и устанавливаем окружение командами (команды для ubuntu 24.04)
    ```shell
    sudo apt update
    sudo apt install curl git unzip nginx
    ```
2. Устанавливаем зависимости для  docker (выполняем по одной команде за раз)
    ```shell
    sudo apt update
    sudo apt install ca-certificates curl
    sudo install -m 0755 -d /etc/apt/keyrings
    sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
    sudo chmod a+r /etc/apt/keyrings/docker.asc
   ```
    Это выполняется одной командой (скопировать-вставить всё сразу)
    ```shell
    sudo tee /etc/apt/sources.list.d/docker.sources <<EOF
    Types: deb
    URIs: https://download.docker.com/linux/ubuntu
    Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
    Components: stable
    Signed-By: /etc/apt/keyrings/docker.asc
    EOF
    ```
3. Устанавливаем сам docker и docker compose
    ```shell
    sudo apt update
    sudo apt install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    ```
4. Проверяем, что всё установилось корректно и работает
    ```shell
    sudo systemctl status docker
    ```
5. В файл `/etc/sudoers` добавляем строку
    ```
    www-data ALL=(ALL) NOPASSWD:ALL
    ```
   Вариант запуска: `sudo visudo` и вносим те же правки

## Делаем копию репозитория

1. Для доступа извне понадобится копия репозитория, доступная с виртуальной машины.
2. Создаём, например, в github копию репозитория и подключаем её к локальному репозиторию командой
   `git remote add public PATH` (PATH – путь к репозиторию)
3. Пушим код в новый репозиторий с ключом `--force`

## Добавляем скрипт развёртывания

1. В репозитории в GitLab
    1. заходим в раздел `Settings -> CI / CD` и добавляем переменные окружения
        1. `SERVER1` - адрес сервера
        2. `SSH_USER` - имя пользователя для входа по ssh
        3. `SSH_PRIVATE_KEY` - приватный ключ, закодированный в base64
        4. `DATABASE_HOST` - `localhost`
        5. `DATABASE_NAME` - `twitter`
        6. `DATABASE_USER` - `my_user`
        7. `DATABASE_PASSWORD` - `1H8a61ceQW7htGRE6iVz`
        8. `RABBITMQ_HOST` - `localhost`
        9. `RABBITMQ_USER` - `my_user`
        10. `RABBITMQ_PASSWORD` - `T1y04lWk167MkyEK3YFk`
2. Создаём файл `deploy/nginx.conf`
    ```
    server {
        listen 80;
        server_name _;
    
        client_max_body_size 25m;
    
        location / {
            proxy_pass http://127.0.0.1:7777;
            proxy_http_version 1.1;
    
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto $scheme;
    
            proxy_read_timeout 60s;
        }
    }
    ```
3. Создаём файл `deploy.sh`
    ```shell
    #!/usr/bin/bash
    
    if [[ $(sudo docker ps -aq| wc -c) -ne 0 ]]; then
      echo "Docker: removing containers"
      sudo docker stop $(docker ps -aq)
      sudo docker container prune --force
    fi
    
    sudo cp deploy/nginx.conf /etc/nginx/conf.d/demo.conf -f
    
    echo "Restarting nginx"
    sudo service nginx restart
    
    echo "Docker: build containers"
    sudo docker compose build
    
    echo "Docker: up containers"
    sudo docker compose up -d
    
    echo "Waiting 10 seconds"
    sleep 10
    docker ps
    
    echo "Installing dependencies"
    sudo docker compose exec --user root php-fpm composer install --no-interaction
    
    echo "Up migrations"
    sudo docker compose exec --user root php-fpm php bin/console doctrine:migrations:migrate --no-interaction
    
    echo "Chown for files"
    sudo chown www-data:www-data . -R
    ```
4. Создаём файл `.gitlab-ci.yml` (не забудьте указать корректный путь к репозиторию в git clone и креды)
    ```yml
    before_script:
      - eval $(ssh-agent -s)
      - echo $SSH_PRIVATE_KEY | base64 -d |tr -d '\r' | ssh-add -
      - mkdir -p ~/.ssh
      - echo -e "Host *\n\tStrictHostKeyChecking no\n\n" > ~/.ssh/config
    
    deploy_server1:
      stage: deploy
      environment:
        name: server1
        url: $SERVER1
      script:
        - ssh $SSH_USER@$SERVER1 "sudo rm -rf /app &&
          sudo mkdir /app &&
          sudo chmod a+rwx /app &&
          cd /app &&
          git clone https://github.com/otusteamedu/symfony-deploy-2025-08.git . &&
          sudo chown www-data:www-data . -R &&
          bash ./deploy.sh $SERVER1 $DATABASE_HOST $DATABASE_USER $DATABASE_PASSWORD $DATABASE_NAME $RABBITMQ_HOST $RABBITMQ_USER $RABBITMQ_PASSWORD"
      only:
        - main
    ```
5. Добавляем код в main-ветку и пушим в GitLab и в публичный репозиторий
6. В репозитории в GitLab в разделе `CI / CD -> Pipelines` можно следить за процессом
7. Выполняем запрос Add user v2 из Postman-коллекции v10 с заменой переменной host на адрес сервера

## Переходим на blue-green deploy

1. На сервере
   1. Переходим в каталог `cd /app/` и останавливаем все контейнеры `docker compose down --remove-orphans` 
   2. Удаляем содержимое каталога `/app`
      ```shell
        sudo rm -rf ./*
        ```
2. Исправляем `.gitlab-ci.yml`
    ```yaml
    before_script:
      - eval $(ssh-agent -s)
      - echo $SSH_PRIVATE_KEY | base64 -d |tr -d '\r' | ssh-add -
      - mkdir -p ~/.ssh
      - echo -e "Host *\n\tStrictHostKeyChecking no\n\n" > ~/.ssh/config
      - export DIR=$(date +%Y%m%d_%H%M%S)
    
    deploy_server1:
      stage: deploy
      environment:
        name: server1
        url: $SERVER1
      script:
        - ssh $SSH_USER@$SERVER1 "cd /app &&
          git clone https://github.com/otusteamedu/symfony-deploy-2025-08.git $DIR &&
          sudo chown www-data:www-data $DIR -R &&
          cd $DIR &&
          sh ./deploy.sh $SERVER1 $DATABASE_HOST $DATABASE_USER $DATABASE_PASSWORD $DATABASE_NAME $RABBITMQ_HOST $RABBITMQ_USER $RABBITMQ_PASSWORD &&
          cd .. &&
          ( [ ! -d /app/current ] || mv -Tf /app/current /app/previous ) &&
          ln -s /app/$DIR /app/current"
      only:
        - main
    ```
3. Пушим код в репозиторий

