<?php

namespace App\Tests\Unit\Application\Teacher\UseCase;

use App\Application\User\UseCase\GetUserRoles;
use App\Domain\Entity\Teacher;
use App\Infrastructure\Factory\CVFactory;
use App\Infrastructure\Factory\TeacherFactory;
use App\Infrastructure\Factory\UserFactory;
use App\Infrastructure\Repository\TeacherRepository;
use App\Infrastructure\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SelectTeachersTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->teacherRepoMock = $this->getMockBuilder(TeacherRepository::class)
            ->onlyMethods(['findTeachersByFilter'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->userRepository = static::getContainer()->get(UserRepository::class);
    }

    public function test_can_select_teachers_by_filter(): void
    {
        $user = UserFactory::createOne(['roles' => GetUserRoles::executeForTeacher()]);
        $teacher = TeacherFactory::createOne(['related_user' => $user]);
        $teacherCV = CVFactory::createOne(['teacher' => $teacher]);

        $arFilter = [
            'rating' => $teacher->getRating(),
            'maxRate' => $teacherCV->getRatePerHour(),
            'yearsOfExperience' => $teacherCV->getYearsOfExperience(),
        ];

        $arSelectedTeachers = [$teacher];
        $this->teacherRepoMock->expects($this->once())
            ->method('findTeachersByFilter')
            ->with($arFilter)
            ->willReturn($arSelectedTeachers);

        $arTeachers = $this->teacherRepoMock->findTeachersByFilter($arFilter);
        $this->assertIsArray($arTeachers);
        foreach ($arTeachers as $item) {
            $this->assertInstanceOf(Teacher::class, $item);
        }

        $this->assertCount(count($arTeachers), $arTeachers);
    }
}
