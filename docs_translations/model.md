Model Configuration

Each model using translation defines:
```
public static $translationFields = ['title', 'description'];
public static $excludedFields = ['url'];
```
Trait Usage
```
use App\Models\Traits\Relations\TranslationRelations;
```
This trait adds:

translations() and original() relationships.

Scopes for filtering by language.

