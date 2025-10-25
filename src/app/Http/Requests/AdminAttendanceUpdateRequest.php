<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // Ruleをインポート

class AdminAttendanceUpdateRequest extends FormRequest
{
    /**
     * リクエストがこのバリデーションを通過できるかを決定します。
     */
    public function authorize(): bool
    {
        // 管理者権限を持つユーザーのみが修正できるように、ここでチェックを実装します
        // 例：return auth()->guard('admin')->check();
        return true;
    }

    /**
     * リクエストに適用されるバリデーションルールを定義します。
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // 修正対象の勤怠IDを取得（ルートまたはフォームから）
        // $attendanceId = $this->route('attendance');
        
        $rules = [
            // ------------------------------------------
            // 必須チェック
            // ------------------------------------------
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i'],
            'remarks' => ['required', 'string', 'max:500'], // 機能要件FN039-4: 備考欄の必須
            
            // ------------------------------------------
            // 時間の順序チェック (カスタムルールを含む)
            // ------------------------------------------
            
            // 出勤時間 < 退勤時間
            'clock_out' => [
                'required',
                'date_format:H:i',
                'after:clock_in' // 退勤時間は出勤時間より後であること
            ],

            // 休憩時間（既存・新規）の配列に対するルール
            'breaks.*.start_time' => [
                'nullable',
                'date_format:H:i',
                // 出勤時間より後であること
                'after:clock_in',
                // 退勤時間より前であること (機能要件FN039-2)
                'before:clock_out'
            ],
            'breaks.*.end_time' => [
                'nullable',
                'date_format:H:i',
                // 休憩開始時間より後であること
                'after:breaks.*.start_time',
                // 退勤時間より前であること (機能要件FN039-3)
                'before:clock_out'
            ],
            
            // 新規休憩時間（もしあれば）
            'new_break.start_time' => [
                'nullable',
                'date_format:H:i',
                'after:clock_in',
                'before:clock_out'
            ],
            'new_break.end_time' => [
                'nullable',
                'date_format:H:i',
                'after:new_break.start_time',
                'before:clock_out'
            ],
        ];

        // 休憩時間のカスタムバリデーション (開始と終了の片方入力禁止)
        // 休憩時間の片方入力禁止は、配列のバリデーションでは複雑になるため、
        // Controllerでデータを整形するか、カスタムバリデーションルールを使用します。
        // ここではメッセージ定義で対応します。

        return $rules;
    }

    /**
     * カスタムエラーメッセージを定義します。
     */
    public function messages(): array
    {
        return [
            'remarks.required' => '備考を記入してください',
            'clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',

            // 休憩時間に関するメッセージ
            'breaks.*.start_time.after' => '休憩時間が不適切な値です',
            'breaks.*.start_time.before' => '休憩時間が不適切な値です',
            'new_break.start_time.after' => '休憩時間が不適切な値です',
            'new_break.start_time.before' => '休憩時間が不適切な値です',
            'breaks.*.end_time.after' => '休憩終了時間が開始時間より前です',
            
            // 休憩終了時間が退勤時間より後になっている場合のチェック
            'breaks.*.end_time.before' => '休憩時間もしくは退勤時間が不適切な値です',
            'new_break.end_time.before' => '休憩時間もしくは退勤時間が不適切な値です',
        ];
    }


}