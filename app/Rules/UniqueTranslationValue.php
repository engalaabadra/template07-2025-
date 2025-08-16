<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

/**
 * Custom validation rule to ensure a translated value is unique per language.
 * Useful when saving multilingual records with lang/translate_id structure.
 */
class UniqueTranslationValue implements Rule
{
    /**
     * The table name to check against.
     *
     * @var string
     */
    protected string $table;

    /**
     * The column name to check for uniqueness.
     *
     * @var string
     */
    protected string $column;

    /**
     * Optional ID to ignore during validation (for update scenarios).
     *
     * @var int|null
     */
    protected ?int $ignoreId;

    /**
     * Optional translation group ID (translate_id) to allow duplicates within the same group.
     *
     * @var int|null
     */
    protected ?int $translateId;

    /**
     * Create a new rule instance.
     *
     * @param string $table The table to query.
     * @param string $column The column to check for uniqueness.
     * @param int|null $ignoreId ID to ignore (used when updating a record).
     * @param int|null $translateId Translation group ID to allow duplicates within the group.
     */
    public function __construct(string $table, string $column, ?int $ignoreId = null, ?int $translateId = null)
    {
        $this->table = $table;
        $this->column = $column;
        $this->ignoreId = $ignoreId;
        $this->translateId = $translateId;
    }

    /**
     * Extract the language code from the given attribute name.
     *
     * @param string $attribute The attribute key from the input (e.g. translations.0.title).
     * @return string The language code, or fallback to current app locale.
     */
    protected function extractLangFromAttribute(string $attribute): string
    {
        preg_match('/translations\.(\d+)\./', $attribute, $matches);

        if (isset($matches[1])) {
            $index = $matches[1];

            $translations = request()->input('translations');

            if (is_array($translations) && isset($translations[$index]['lang'])) {
                return $translations[$index]['lang'];
            }
        }

        return localeLang();
    }


     /**
     * This rule ensures that the given value is unique **per language** across the entire table.
     * It prevents duplicate values in the same language, even for different translate_id groups.
     * 
     * ✅ Allowed: Same value in different languages
     * ✅ Allowed: Same value for the same record (on update)
     * ❌ Not allowed: Same value in the same language for any other record
     * 
     * Summery : not allowed store same a value in ((((same lang)))) whether with diff. translate_id or same translate_id
     * 
     * 
     * @param string $attribute The input attribute being validated.
     * @param mixed $value The value of the attribute.
     * @return bool Whether the value is unique in the given context.
     * 
     */
    public function passes($attribute, $value): bool
    {
        // Extract language code from the attribute name (e.g., 'translations.0.title' → 'ar')
        $lang = $this->extractLangFromAttribute($attribute);
        // we want uniqueness across all items , only in this lang
        $query = DB::table($this->table)
            ->where('lang', $lang)
            ->where($this->column, $value);
        // Ignore current record (only when updating)
        $query->where('translate_id', '!=', $this->ignoreId);

        // If any such row exists, it means the value is already taken in this lang
        return !$query->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string Error message if the rule fails.
     */
    public function message(): string
    {
        return __('validation.unique');
    }
}
