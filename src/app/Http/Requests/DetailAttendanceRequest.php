<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DetailAttendanceRequest extends FormRequest
{
    /**
     * バリデーションが最初に見つかった失敗で停止するかどうか。
     * * @var bool
     */
    protected $stopOnFirstFailure = false;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        // 💡 修正: ルートパラメータのIDを取得 (新規登録: 0, 修正: >0)
        $id = $this->route('id'); 
        $isNewRegistration = ($id == 0);

        // 💡 修正: 新規登録時のみ clock_in を required にする
        $clockInRules = $isNewRegistration 
            ? ['required', 'date_format:H:i'] 
            : ['nullable', 'date_format:H:i'];
            
        // 💡 追記: 新規登録時のみ target_date (対象日付) を required にする
        $targetDateRules = $isNewRegistration
            ? ['required', 'date_format:Y-m-d']
            : ['nullable'];
            
        return [
            // 💡 修正: 動的に定義したルールを適用
            'clock_in' => $clockInRules, 
            'clock_out' => ['nullable', 'date_format:H:i', 'after:clock_in'],
            
            // 💡 追記: 新規登録に必要な対象日付のバリデーション
            'target_date' => $targetDateRules,
            
            // ----------------------------------------------------
            // 💡 既存の休憩時間: breaks[ID][start_time]形式に対応
            // ----------------------------------------------------
            'breaks.*.start_time' => [
                'required_with:breaks.*.end_time',
                'nullable', // 両方空は許容
                'date_format:H:i',
                'after:clock_in',
                'before:breaks.*.end_time',
                'before:clock_out',
            ],
            'breaks.*.end_time' => [
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
            'remarks' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages()
    {
        return [
            // ----------------------------------------------------
            // 💡 新規登録時の必須チェックメッセージを追記
            // ----------------------------------------------------
            'clock_in.required' => '出勤時間もしくは退勤時間が不適切な値です', // 新規登録時に適用される
            // ----------------------------------------------------
            // 休憩時間（既存・新規）のカスタムエラーメッセージ
            // ----------------------------------------------------
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
            // 出勤時間・退勤時間のカスタムエラーメッセージ
            // ----------------------------------------------------
            // 'clock_in.before' => '出勤時間もしくは退勤時間が不適切な値です', // before:clock_out を削除したため通常は不要
            'clock_out.after' => '出勤時間が不適切な値です',

            // ----------------------------------------------------
            // 備考欄のカスタムエラーメッセージ
            // ----------------------------------------------------
            'remarks.required' => '備考を記入してください',
            
            // 必須チェック（片方のみ入力された場合）のメッセージを上書き
            'breaks.*.start_time.required_with' => '休憩時間が不適切な値です',
            'breaks.*.end_time.required_with' => '休憩時間が不適切な値です',
            'new_break.start_time.required_with' => '休憩時間が不適切な値です',
            'new_break.end_time.required_with' => '休憩時間が不適切な値です',
        ];
    }
    
    public function attributes()
    {
        return [
            'clock_in' => '出勤時間',
            'clock_out' => '退勤時間',
            'target_date' => '対象日', // 💡 追記
            'breaks.*.start_time' => '休憩開始時間',
            'breaks.*.end_time' => '休憩終了時間',
            'new_break.start_time' => '休憩開始時間',
            'new_break.end_time' => '休憩終了時間',
            'remarks' => '備考',
        ];
    }
}