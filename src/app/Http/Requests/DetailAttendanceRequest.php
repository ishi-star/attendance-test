<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DetailAttendanceRequest extends FormRequest
{
    /**
     * バリデーションが最初に見つかった失敗で停止するかどうか。
     * * @var bool
     */
    protected $stopOnFirstFailure = true;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // 💡 出勤・退勤時間は必須
            'clock_in' => ['required', 'date_format:H:i', 'before:clock_out'],
            'clock_out' => ['required', 'date_format:H:i', 'after:clock_in'],
            
            // ----------------------------------------------------
            // 💡 既存の休憩時間: breaks[ID][start_time]形式に対応
            // ----------------------------------------------------
            'breaks.*.start_time' => [
                // 終了時間が入力されていれば開始時間は必須 (片方だけ空を許さない)
                'required_with:breaks.*.end_time',
                'nullable', // 両方空は許容
                'date_format:H:i',
                'after:clock_in',
                'before:breaks.*.end_time',
            ],
            'breaks.*.end_time' => [
                // 開始時間が入力されていれば終了時間は必須
                'required_with:breaks.*.start_time',
                'nullable', // 両方空は許容
                'date_format:H:i',
                'after:breaks.*.start_time',
                'before_or_equal:clock_out', // ⭐ 退勤時間との比較
            ],

            // ----------------------------------------------------
            // 💡 新規追加の休憩時間: new_break[start_time]形式に対応
            // ----------------------------------------------------
            'new_break.start_time' => [
                'required_with:new_break.end_time',
                'nullable',
                'date_format:H:i',
                'after:clock_in',
                'before:new_break.end_time',
            ],
            'new_break.end_time' => [
                'required_with:new_break.start_time',
                'nullable',
                'date_format:H:i',
                'after:new_break.start_time',
                'before_or_equal:clock_out', // ⭐ 退勤時間との比較
            ],
            
            // 💡 備考欄は必須項目であること
            'remarks' => ['required', 'string', 'max:500'], // Bladeに合わせてremarkをremarksに修正
        ];
    }

    public function messages()
    {
        return [
            // ----------------------------------------------------
            // 💡 休憩時間（既存・新規）のカスタムエラーメッセージ
            // ----------------------------------------------------
            
            // 必須チェック（片方のみ入力された場合）のメッセージを上書き
            'breaks.*.start_time.required_with' => '休憩開始時間は、必ず指定してください。',
            'breaks.*.end_time.required_with' => '休憩終了時間は、必ず指定してください。',
            'new_break.start_time.required_with' => '休憩開始時間は、必ず指定してください。',
            'new_break.end_time.required_with' => '休憩終了時間は、必ず指定してください。',

            // ⭐ 退勤時間との比較 (before_or_equal:clock_out)
            'breaks.*.end_time.before_or_equal' => '休憩時間もしくは退勤時間が不適切な値です',
            'new_break.end_time.before_or_equal' => '休憩時間もしくは退勤時間が不適切な値です',
            
            // 休憩開始/終了の前後関係（after, before）
            'breaks.*.start_time.after' => '休憩時間が不適切な値です',
            'breaks.*.start_time.before' => '休憩時間が不適切な値です',
            'breaks.*.end_time.after' => '休憩時間が不適切な値です',

            'new_break.start_time.after' => '休憩時間が不適切な値です',
            'new_break.start_time.before' => '休憩時間が不適切な値です',
            'new_break.end_time.after' => '休憩時間が不適切な値です',

            // ----------------------------------------------------
            // 💡 出勤時間・退勤時間のカスタムエラーメッセージ
            // ----------------------------------------------------
            'clock_in.before' => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',

            // ----------------------------------------------------
            // 💡 備考欄のカスタムエラーメッセージ
            // ----------------------------------------------------
            'remarks.required' => '備考を記入してください', // Bladeに合わせてremarkをremarksに修正
        ];
    }
    
    public function attributes()
    {
        return [
            'clock_in' => '出勤時間',
            'clock_out' => '退勤時間',
            'breaks.*.start_time' => '休憩開始時間',
            'breaks.*.end_time' => '休憩終了時間',
            'new_break.start_time' => '休憩開始時間',
            'new_break.end_time' => '休憩終了時間',
            'remarks' => '備考', // Bladeに合わせてremarkをremarksに修正
        ];
    }
}