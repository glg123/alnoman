<?php

namespace Database\Seeders;

use App\Models\BenefitDistribution;
use App\Models\Family;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1) حساب الأدمن
        $admin = User::create([
            'name'     => 'مدير النظام',
            'username' => 'admin',
            'email'    => 'admin@camp.local',
            'password' => Hash::make('admin1234'),
            'role'     => 'admin',
        ]);

        // 2) مستخدم لديه بيانات معتمدة (approved)
        $user1 = User::create([
            'name'     => 'مستخدم تجريبي 1',
            'username' => 'user1',
            'email'    => 'user1@camp.local',
            'password' => Hash::make('user12345'),
            'role'     => 'user',
        ]);

        $family1 = Family::create([
            'user_id'          => $user1->id,
            'full_name'        => 'محمد أحمد خليل يوسف',
            'national_id'      => '900000001',
            'wife_name'        => 'سارة علي محمود',
            'wife_national_id' => '900000002',
            'members_count'    => 4,
            'status'           => 'approved',
            'approved_by'      => $admin->id,
            'approved_at'      => now(),
        ]);

        $child1 = $family1->children()->create([
            'full_name'   => 'أحمد محمد أحمد',
            'national_id' => '900000003',
            'age'         => 8,
        ]);

        $family1->specialCases()->create([
            'child_id'    => $child1->id,
            'type'        => 'إعاقة حركية',
            'description' => 'يحتاج كرسي متحرك',
        ]);

        // 3) مستخدم لديه بيانات بانتظار الموافقة (pending)
        $user2 = User::create([
            'name'     => 'مستخدم تجريبي 2',
            'username' => 'user2',
            'email'    => 'user2@camp.local',
            'password' => Hash::make('user12345'),
            'role'     => 'user',
        ]);

        $family2 = Family::create([
            'user_id'          => $user2->id,
            'full_name'        => 'خالد إبراهيم موسى سالم',
            'national_id'      => '900000010',
            'wife_name'        => 'ليلى حسن عودة',
            'wife_national_id' => '900000011',
            'members_count'    => 3,
            'status'           => 'pending',
        ]);

        $family2->children()->create([
            'full_name'   => 'يوسف خالد إبراهيم',
            'national_id' => null,
            'age'         => 2,
        ]);

        // 4) مؤسسة تجريبية + توزيع استفادة للأسرة المعتمدة
        $org = Organization::create([
            'name'        => 'جمعية الأمل الخيرية',
            'phone'       => '0590000000',
            'address'     => 'المخيم — المنطقة الشمالية',
            'description' => 'جمعية تقدم مساعدات غذائية وصحية للأسر المحتاجة.',
        ]);

        $dist = BenefitDistribution::create([
            'organization_id' => $org->id,
            'type'            => 'كوبون صحي وغذائي',
            'description'     => 'كوبون شهري لشراء مواد غذائية ومستلزمات صحية.',
            'distributed_at'  => now()->toDateString(),
            'created_by'      => $admin->id,
        ]);
        $dist->families()->attach($family1->id);
    }
}
