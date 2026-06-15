<?php

namespace App\Enums\Personnel;

enum PersonnelPosition: string
{
    // School Heads
    case PrincipalI = 'Principal I';
    case PrincipalII = 'Principal II';
    case PrincipalIII = 'Principal III';
    case PrincipalIV = 'Principal IV';

    case AssistantSchoolPrincipalI = 'Assistant School Principal I';
    case AssistantSchoolPrincipalII = 'Assistant School Principal II';
    case AssistantSchoolPrincipalIII = 'Assistant School Principal III';

    // Academic Leadership
    case HeadTeacherI = 'Head Teacher I';
    case HeadTeacherII = 'Head Teacher II';
    case HeadTeacherIII = 'Head Teacher III';
    case HeadTeacherIV = 'Head Teacher IV';
    case HeadTeacherV = 'Head Teacher V';
    case HeadTeacherVI = 'Head Teacher VI';

    case MasterTeacherI = 'Master Teacher I';
    case MasterTeacherII = 'Master Teacher II';
    case MasterTeacherIII = 'Master Teacher III';
    case MasterTeacherIV = 'Master Teacher IV';

    // Teachers
    case TeacherI = 'Teacher I';
    case TeacherII = 'Teacher II';
    case TeacherIII = 'Teacher III';

    // Student Services
    case GuidanceCounselorI = 'Guidance Counselor I';
    case LibrarianI = 'Librarian I';

    // Admin
    case AdministrativeOfficerII = 'Administrative Officer II';
    case AdministrativeAssistantI = 'Administrative Assistant I';
    case AdministrativeAssistantII = 'Administrative Assistant II';
    case AdministrativeAssistantIII = 'Administrative Assistant III';

    case AdministrativeAideI = 'Administrative Aide I';
    case AdministrativeAideII = 'Administrative Aide II';
    case AdministrativeAideIII = 'Administrative Aide III';
    case AdministrativeAideIV = 'Administrative Aide IV';
    case AdministrativeAideV = 'Administrative Aide V';
    case AdministrativeAideVI = 'Administrative Aide VI';

    // Finance
    case AccountantI = 'Accountant I';
    case Bookkeeper = 'Bookkeeper';

    // Health
    case NurseI = 'Nurse I';
    case DentistI = 'Dentist I';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
