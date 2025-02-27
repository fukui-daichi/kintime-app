<?php

namespace Tests\Unit\Services\Timecard;

use App\Constants\WorkTimeConstants;
use App\Exceptions\Timecard\ClockInNotFoundException;
use App\Exceptions\Timecard\DuplicateClockInException;
use App\Models\Timecard;
use App\Models\User;
use App\Repositories\Interfaces\TimecardRepositoryInterface;
use App\Services\Timecard\TimecardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Mockery;

class TimecardServiceTest extends TestCase
{
    use DatabaseTransactions;

    private $service;
    private $repository;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        // リポジトリのモック作成
        $this->repository = Mockery::mock(TimecardRepositoryInterface::class);

        // 型キャストを明示的に行う
        /** @var TimecardRepositoryInterface $repository */
        $repository = $this->repository;

        // テスト用のサービスクラスインスタンス作成
        $this->service = new TimecardService($repository);

        // テスト用ユーザーの作成はモックに変更
        // UserのFactoryを使用する代わりに、既存のUserを取得するか、モックユーザーを作成
        if ($existingUser = User::first()) {
            $this->user = $existingUser;
        } else {
            // 既存のユーザーがいない場合のみ作成（まれなケース）
            $this->user = User::factory()->create(['user_type' => 'user']);
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     * 出勤打刻の正常系テスト
     */
    public function 出勤打刻で新しい勤怠レコードが作成される()
    {
        // モックの設定
        $this->repository->shouldReceive('hasTodayTimecard')
            ->once()
            ->with($this->user->id)
            ->andReturn(false);

        $this->repository->shouldReceive('create')
            ->once()
            ->andReturn(new Timecard());

        // 実行（例外が発生しなければテスト成功）
        $this->service->clockIn($this->user->id);

        // アサーションは必要ないが、テストが実行されたことを明示
        $this->assertTrue(true);
    }

    /**
     * @test
     * 出勤打刻の異常系テスト - 重複打刻
     */
    public function 出勤済みの場合に出勤打刻すると例外が発生する()
    {
        // モックの設定
        $this->repository->shouldReceive('hasTodayTimecard')
            ->once()
            ->with($this->user->id)
            ->andReturn(true);

        // 例外が発生することを期待
        $this->expectException(DuplicateClockInException::class);

        // 実行
        $this->service->clockIn($this->user->id);
    }

    /**
     * @test
     * 退勤打刻の正常系テスト
     */
    public function 退勤打刻で勤怠レコードが更新される()
    {
        // 現在時刻の1時間前を出勤時刻とする勤怠レコードを作成
        $clockIn = Carbon::now()->subHour();
        $timecard = new Timecard([
            'user_id' => $this->user->id,
            'date' => Carbon::now()->toDateString(),
            'clock_in' => $clockIn->toTimeString(),
            'break_time' => WorkTimeConstants::DEFAULT_BREAK_MINUTES,
            'status' => 'working',
        ]);

        // モックの設定
        $this->repository->shouldReceive('getTodayWorkingTimecard')
            ->once()
            ->with($this->user->id)
            ->andReturn($timecard);

        $this->repository->shouldReceive('update')
            ->once()
            ->andReturn(true);

        // 実行
        $this->service->clockOut($this->user->id);

        // アサーション
        $this->assertTrue(true);
    }

    /**
     * @test
     * 退勤打刻の異常系テスト - 出勤記録なし
     */
    public function 出勤記録がない場合に退勤打刻すると例外が発生する()
    {
        // モックの設定
        $this->repository->shouldReceive('getTodayWorkingTimecard')
            ->once()
            ->with($this->user->id)
            ->andReturn(null);

        // 例外が発生することを期待
        $this->expectException(ClockInNotFoundException::class);

        // 実行
        $this->service->clockOut($this->user->id);
    }

    /**
     * @test
     * 月別勤怠データ取得のテスト
     */
    public function 月別勤怠データの取得結果が正しい構造を持つ()
    {
        // モックの設定
        $this->repository->shouldReceive('getMonthlyTimecards')
            ->once()
            ->andReturn(collect([]));

        // 実行
        $result = $this->service->getMonthlyTimecardData($this->user->id);

        // アサーション
        $this->assertIsArray($result);
        $this->assertArrayHasKey('timecards', $result);
        $this->assertArrayHasKey('targetDate', $result);
        $this->assertArrayHasKey('previousMonth', $result);
        $this->assertArrayHasKey('nextMonth', $result);
        $this->assertArrayHasKey('showNextMonth', $result);
        $this->assertArrayHasKey('years', $result);
        $this->assertArrayHasKey('months', $result);
    }

    /**
     * @test
     * 日次勤怠データ取得のテスト
     */
    public function 日次勤怠データの取得結果が正しい構造を持つ()
    {
        // モックの設定
        $this->repository->shouldReceive('getTodayTimecard')
            ->once()
            ->with($this->user->id)
            ->andReturn(null);

        // 実行
        $result = $this->service->getDailyTimecardData($this->user->id);

        // アサーション
        $this->assertIsArray($result);
        $this->assertArrayHasKey('timecard', $result);
        $this->assertArrayHasKey('canClockIn', $result);
        $this->assertArrayHasKey('canClockOut', $result);
        $this->assertArrayHasKey('timecardData', $result);
        $this->assertTrue($result['canClockIn']);
        $this->assertFalse($result['canClockOut']);
    }
}
