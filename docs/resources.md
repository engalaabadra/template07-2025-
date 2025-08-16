
### BaseResource

`BaseResource` is a custom API resource class that extends Laravel's `JsonResource`. It provides flexible and dynamic transformation of Eloquent models (or arrays) into JSON-friendly arrays for API responses.

This resource class serves as a base for other API resources, promoting DRY principles and consistency across API endpoints.

***Features***

- If the resource is an **array**, it returns the data as-is without modification.
- If the resource is an **Eloquent model**, it:
  - Extracts only the model's **fillable attributes** for output.
  - Automatically appends any defined **accessors** (e.g., `is_active_text` or other appended attributes).
  - Includes **translation data** if the `translations` relation is loaded, mapping all translatable fields with their respective languages.
  - Appends **formatted timestamps** like `created_at_text` and optionally `deleted_at_text` if soft deletes are enabled.

- If the resource is neither a model nor an array, it casts it to a plain array.

***Usage***

This base resource simplifies creating API resources by dynamically handling common model attributes and translation relationships, ensuring consistent, clean, and localized API output.

***Translation Handling***

The method `getTranslationData()` extracts translation records associated with the model and formats them into an array containing language codes (`lang`), translation IDs, and translatable fields as defined statically in the model's `$translationFields` property.

---

***Example workflow inside `toArray`:***

1. Check if resource is array → return as-is.
2. Else if resource is model → get fillable attributes.
3. Append dynamic accessors if any.
4. Append translations if loaded.
5. Append timestamps.
6. Return final array.

---

### getTranslationData($translations, $model)

Fetches all translations for the current resource to be used when rendering.

- **Parameters:**
  - `$translations` (Collection|null): The collection of translation models or null.
  - `$model` (string): The model class name to access static translation fields.

- **Returns:**
  - An array of translation data, each containing:
    - `id`: Translation record ID.
    - `translate_id`: Group ID for related translations.
    - `lang`: Language code of the translation.
    - Translatable fields as defined by the model's static `$translationFields` property (e.g., `username`, `full_name`, `nick_name`, `address`).

- **Description:**
  - If no translations are provided, returns an empty array.
  - Maps each translation to only include fields specified in the model’s `$translationFields`.
  - Returns the mapped translations as an array for easy inclusion in API responses.

- **Example output:**
  ```php
  [
      [
          'id' => 1,
          'translate_id' => 10,
          'lang' => 'ar',
          'username' => 'اسم المستخدم',
          'full_name' => 'الاسم الكامل',
          'nick_name' => 'اللقب',
          'address' => 'العنوان',
      ],
      [
          'id' => 2,
          'translate_id' => 10,
          'lang' => 'en',
          'username' => 'username',
          'full_name' => 'Full Name',
          'nick_name' => 'Nickname',
          'address' => 'Address',
      ],
  ]

### UserResource

This resource class transforms the **User** model and its related data into a clean, structured API response. It extends a base resource to inherit common fields and enriches the output with related models and translation support.

---

***Purpose***

- Serialize the User model's attributes.
- Include related data such as:
  - **Profile** (with its own resource and translations).
  - **Country** information.
  - **Roles** assigned to the user.
  - **Files** and **Image** resources attached to the user.
- Provide multilingual translations for profile data.
- Customize API output format consistently.

---

***Key Features***

- **Conditional loading**: Uses Laravel's `whenLoaded()` method to only include relations when they are eager loaded, optimizing response size.
- **Profile translation support**: Extracts translations for related profile fields, supporting multilingual APIs.
- **Nested Resources**: Wraps related models into their respective resource classes (`ProfileResource`, `CountryResource`, `RoleResource`, `FileResource`).
- **Extends `BaseResource`**: Leverages a base resource for common serialization logic, such as including fillable model attributes.

---

***Example Output Structure***

```json
{
  "lang": "ar",
  "email": "employee@example.com",
  "username": "موظف1",
  "profile": {
    // profile attributes here...
  },
  "country": {
    // country attributes here...
  },
  "roles": [
    // roles data here...
  ],
  "files": [
    // attached files here...
  ],
  "image": {
    // single image resource here...
  },
  "translations": [
    {
      "lang": "en",
      "username": "employee Name"
    },
    {
      "lang": "fr",
      "username": "Nom de l'enseignant"
    }
  ]
}
