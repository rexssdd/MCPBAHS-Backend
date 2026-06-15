
-- =====================================================
-- ROLES
-- =====================================================
insert into roles (name)
values
('admin'),
('principal'),
('registrar'),
('teacher')
on conflict (name) do nothing;

-- =====================================================
-- USERS (System Admins)
-- =====================================================
insert into users (uuid, name, email, role, password, account_status, email_verified_at)
values
(gen_random_uuid(), 'System Admin', 'admin@gmail.com', 'admin', '1234', 'active', now()),
(gen_random_uuid(), 'Principal User', 'principal@gmail.com', 'principal', '1234', 'active', now()),
(gen_random_uuid(), 'Registrar User', 'registrar@gmail.com', 'registrar', '1234', 'active', now()),
(gen_random_uuid(), 'Teacher User', 'teacher@gmail.com', 'teacher', '1234', 'active', now())
on conflict (email) do nothing;

-- =====================================================
-- ROLE ASSIGNMENT (user_roles)
-- =====================================================
insert into user_roles (user_id, role_id)
select u.id, r.id
from users u
join roles r on r.name = u.role
on conflict do nothing;

-- =====================================================
-- PERSONNELS (30 fake records)
-- NOTE: Supabase has no Faker → using SQL generator approach
-- =====================================================
insert into personnels (
    uuid,
    personnel_id_number,
    first_name,
    middle_name,
    last_name,
    email,
    phone_number,
    date_of_birth,
    sex,
    country,
    region,
    province,
    brgy_street_address,
    city,
    postal_code,
    teaching_load,
    position,
    department,
    employment_status
)
select
    gen_random_uuid(),
    (100000 + gs)::int,
    'First' || gs,
    'M',
    'Last' || gs,
    'personnel' || gs || '@mail.com',
    '09' || (900000000 + gs)::text,
    date '1995-01-01' + (gs || ' days')::interval,
    case when random() > 0.5 then 'Male' else 'Female' end,
    'Philippines',
    'Davao Region',
    'Davao del Sur',
    'Street ' || gs,
    'Davao City',
    '8000',
    (6 + (random() * 18))::int,
    'Teacher',
    'Education',
    'Active'
from generate_series(1,30) gs;

-- =====================================================
-- SAMPLE REPORTS
-- =====================================================
insert into reports (
    uuid,
    form_type,
    school_year,
    file_path,
    status,
    submitted_by,
    remarks
)
select
    gen_random_uuid(),
    'Enrollment',
    '2025-2026',
    '/files/report' || gs || '.pdf',
    'ForAdminApproval',
    null,
    'Sample report ' || gs
from generate_series(1,10) gs;

-- =====================================================
-- SECTIONS
-- =====================================================
insert into sections (
    uuid,
    section_name,
    grade_level,
    school_year
)
values
(gen_random_uuid(), 'Section A', 'Grade 7', '2025-2026'),
(gen_random_uuid(), 'Section B', 'Grade 8', '2025-2026');

-- =====================================================
-- CLASS SCHEDULES SAMPLE
-- =====================================================
insert into class_schedules (
    uuid,
    room_no,
    subject,
    school_year,
    semester,
    days,
    start_time,
    end_time
)
values
(gen_random_uuid(), 'Room 101', 'Math', '2025-2026', '1st', '["Mon","Wed"]', '08:00', '09:30'),
(gen_random_uuid(), 'Room 102', 'Science', '2025-2026', '1st', '["Tue","Thu"]', '10:00', '11:30');

-- =====================================================
-- NOTIFICATIONS SAMPLE
-- =====================================================
insert into notifications (
    user_id,
    type,
    title,
    message,
    report_uuid
)
select
    u.id,
    'report',
    'New Report',
    'A new report has been submitted',
    null
from users u
limit 5;
