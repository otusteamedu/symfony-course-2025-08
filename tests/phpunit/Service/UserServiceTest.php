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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserServiceTest extends TestCase
{
    private const PASSWORD_HASH = 'my_hash';
    private const DEFAULT_AGE = 18;
    private const DEFAULT_IS_ACTIVE = true;
    private const DEFAULT_ROLES = ['ROLE_USER'];

    #[DataProvider('createDataForTestCreate')]
    public function testWllNotCreate(array $expectedData): void
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

    private function getCreateUserModel(): CreateUserModel
    {
        return new CreateUserModel(
            'someLogin',
            'somePhone',
            CommunicationChannelEnum::Phone
        );
    }

    public static function createDataForTestCreate(){
        yield 'first case' => [self::getExpectedData()];
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