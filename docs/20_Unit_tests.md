# ПРАКТИКА
Запускаем контейнеры командой `docker-compose up -d`

## Устанавливаем PHPUnit 

1. Заходим в контейнер командой `docker exec -it php sh`. Дальнейшие команды выполняем из контейнера
2. Добавляем пакет в **dev-режиме**  `composer require symfony/test-pack --dev`
3. Исправляем в composer.json секцию `autoload-dev`
    ```json
    "autoload-dev": {
        "psr-4": {
            "UnitTests\\": "tests/unit"
        }
    },
    ```
4. Можно запустить тесты командой `vendor/bin/phpunit`


## Пишем тест с мок-сервисом

1. Добавляем класс `UnitTests\Service\UserServiceTest`
    ```php
<?php

namespace UnitTests\Service;

use App\Domain\Entity\EmailUser;
use App\Domain\Entity\PhoneUser;
use App\Domain\Model\CreateUserModel;
use App\Domain\Service\UserService;
use App\Domain\ValueObject\CommunicationChannelEnum;
use App\Infrastructure\Repository\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserServiceTest extends TestCase
{
    private const PASSWORD_HASH = 'my_hash';
    private const DEFAULT_AGE = 18;
    private const DEFAULT_IS_ACTIVE = true;
    private const DEFAULT_ROLES = ['ROLE_USER'];

    public static function createDataForTestCreate(){
        yield 'without exception' => [self::getExpectedData(), new NotFoundHttpException()]; 
    }


    #[DataProvider('createDataForTestCreate')]
    public function testWllNotCreate(array $expectedData, $exception): void
    {
        //arrange
        $userService = $this->prepareUserService();

        // act
        $user = $userService->create($this->getCreateUserModel());

        // assert
        $actualData = [
            'class' => get_class($user),
            'login' => $user->getLogin(),
            'email' => ($user instanceof EmailUser) ? $user->getEmail() : null,
            'phone' => ($user instanceof PhoneUser) ? $user->getPhone() : null,
            'passwordHash' => $user->getPassword(),
            'age' => $user->getAge(),
            'isActive' => $user->isActive(),
            'roles' => $user->getRoles(),
        ];
        static::assertSame($expectedData, $actualData);
    }

    private function prepareUserService(): UserService
    {
        $userRepository = $this->createMock(UserRepository::class);

        $userPasswordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $userPasswordHasher->method('hashPassword')->willReturn(self::PASSWORD_HASH);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $userService = new UserService($userRepository, $userPasswordHasher, $eventDispatcher);

        return $userService;
    }

    private function getCreateUserModel(): CreateUserModel
    {
        return new CreateUserModel(
            'someLogin',
            'somePhone',
            CommunicationChannelEnum::Phone
        );
    }

    private static function getExpectedData(): array
    {
        return [
            'class' => PhoneUser::class,
            'login' => 'someLogin',
            'email' => null,
            'phone' => 'somePhone',
            'passwordHash' => self::PASSWORD_HASH,
            'age' => self::DEFAULT_AGE,
            'isActive' => self::DEFAULT_IS_ACTIVE,
            'roles' => self::DEFAULT_ROLES,
        ];
    }
}
    ```

5. Запускаем тесты командой `vendor/bin/phpunit` 

3. В интерфейсе `App\Domain\Entity\EntityInterface` исправляем декларацию метода `getId`
    ```php
    public function getId(): ?int;
    ```
4. В классе `App\Domain\Entity\User` исправляем метод `getId`
    ```php
    public function getId(): ?int
    {
        return $this->id;
    }
 ```
5. Ещё раз запускаем тесты, видим дальнейшую ошибку.

## Добавляем поведение к мок-методу


1. Отменяем правки в интерфейсе `App\Domain\Entity\EntityInteface` и классе `App\Domain\Entity\User`
2. В классе `UnitTests\Service\UserServiceTest` исправляем метод `prepareUserService`
    ```php
    private function prepareUserService(): UserService
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('create')->with(
            $this->callback(static function ($user) {
                $user->setId(1);
                return true;
            })
        );

        $userPasswordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $userPasswordHasher->method('hashPassword')->willReturn(self::PASSWORD_HASH);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $userService = new UserService($userRepository, $userPasswordHasher, $eventDispatcher);

        return $userService;
    }
  ```
 