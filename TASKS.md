# DropBlockEditor Improvement Plan

> This document outlines the incremental improvement plan for the DropBlockEditor package, focusing on DX improvements and feature additions for website building use cases.

## Project Context

- **Primary Use Case:** Websites (2nd: Email templates)
- **Focus Areas:** DX improvements and feature additions
- **Approach:** Incremental improvements for easy testing
- **Goal:** Correct design/architecture decisions before they become problematic

---

## PHASE 1: DX & Architecture Improvements

**Priority:** HIGH  
**Status:** Not Started  
**Goal:** Solid foundation with better type safety and validation

### 1.1 Add `BlockInterface` Contract
**Goal:** Ensure all blocks implement a consistent API

**Current Problem:**
- No enforced contract for block classes
- IDE can't provide autocomplete for block methods
- Difficult to create custom block types

**Implementation:**
- Create `src/Contracts/BlockInterface.php`
- Define required methods: `render()`, `getData()`, `validate()`, `getSchema()`
- Update `Block` base class to implement interface
- Add type hints throughout codebase

**Files to Modify:**
- `src/Contracts/BlockInterface.php` (new)
- `src/Blocks/Block.php`
- `src/Components/DropBlockEditor.php`

**Testing:**
- Ensure all example blocks still work
- Verify interface compliance

---

### 1.2 Typed BlockData with Schema Definition
**Goal:** Replace loose data arrays with typed schemas

**Current Problem:**
```php
// No validation, no IDE support
public array $data = ['title' => '', 'content' => ''];
```

**Proposed Solution:**
```php
public function schema(): BlockSchema {
    return BlockSchema::make()
        ->string('title', 'Title')->required()->max(100)
        ->textarea('content', 'Content')->max(500)
        ->image('hero_image', 'Hero Image')->dimensions(1200, 600)
        ->select('alignment', 'Alignment')
            ->options(['left', 'center', 'right'])
            ->default('left');
}
```

**Benefits:**
- IDE autocomplete for data keys
- Automatic form generation for edit components
- Built-in validation
- Self-documenting blocks

**Files to Create:**
- `src/Schemas/BlockSchema.php`
- `src/Schemas/FieldTypes/StringField.php`
- `src/Schemas/FieldTypes/TextareaField.php`
- `src/Schemas/FieldTypes/ImageField.php`
- `src/Schemas/FieldTypes/SelectField.php`
- `src/Schemas/FieldTypes/NumberField.php`
- `src/Schemas/FieldTypes/BooleanField.php`

**Files to Modify:**
- `src/Blocks/Block.php`
- `src/Components/BlockEditComponent.php`
- `src/Commands/block.stub`
- `src/Commands/block.edit-component.stub`

**Testing:**
- Create test blocks with schemas
- Verify validation works
- Test form auto-generation

---

### 1.3 Block Data Validation System
**Goal:** Automatic validation before render with clear error messages

**Current Problem:**
- No validation on block data
- Invalid data can break rendering
- No feedback to users about data issues

**Implementation:**
- Integrate with Laravel's Validator
- Validate on block creation and update
- Display validation errors in edit component
- Graceful degradation for invalid blocks

**Files to Modify:**
- `src/Blocks/Block.php`
- `src/Components/BlockEditComponent.php`
- `src/Components/DropBlockEditor.php`

**Testing:**
- Test validation rules
- Test error display
- Test graceful handling of invalid data

---

### 1.4 Better Error Handling & Logging
**Goal:** Clear error messages for developers and graceful degradation

**Current Problem:**
- Silent failures when blocks are missing
- No logging for debugging
- Unclear error messages

**Implementation:**
- Create `BlockNotFoundException`
- Add logging for block operations
- Fallback rendering for missing blocks
- Development mode with detailed errors

**Files to Create:**
- `src/Exceptions/BlockNotFoundException.php`
- `src/Exceptions/ValidationException.php`

**Files to Modify:**
- `src/Parsers/Html.php`
- `src/Components/DropBlockEditor.php`

**Testing:**
- Test missing block scenarios
- Test validation failures
- Verify logging works

---

### 1.5 IDE Support & Type Safety
**Goal:** Full IDE autocomplete and type checking

**Implementation:**
- Add generics support where possible
- PHPStan/Psalm annotations
- Better return type hints
- Template types for data arrays

**Files to Modify:**
- All PHP files for type annotations
- `phpstan.neon.dist` for stricter rules

**Testing:**
- Run PHPStan analysis
- Verify IDE autocomplete works

---

## PHASE 2: Block Nesting & Layout System

**Priority:** HIGH  
**Status:** Not Started  
**Goal:** Support complex page layouts

**Architecture Decision Needed:**
- **Option A:** Recursive block structure (blocks contain `children` array)
- **Option B:** Flat structure with parent references (nested sets)
- **Option C:** Slot-based system (parent defines slots, children fill them)

**Recommendation:** Option C (Slot-based) for website building

### 2.1 Container Block Type
**Goal:** Blocks that can contain other blocks

**Implementation:**
- Create `ContainerBlock` class extending `Block`
- Define slot system for child placement
- Support multiple slots per container

**Example:**
```php
class TwoColumnLayout extends ContainerBlock
{
    public function slots(): array
    {
        return [
            Slot::make('left', 'Left Column')->width('50%'),
            Slot::make('right', 'Right Column')->width('50%'),
        ];
    }
}
```

**Files to Create:**
- `src/Blocks/ContainerBlock.php`
- `src/Slots/Slot.php`
- `src/Slots/SlotCollection.php`

**Files to Modify:**
- `src/Parsers/Html.php` (handle nested rendering)
- `src/Components/DropBlockEditor.php` (nested drag-drop)
- `resources/js/editor.js` (slot-aware dragging)

---

### 2.2 Nesting Depth Management
**Goal:** Prevent infinite loops and UI clutter

**Implementation:**
- Configurable max nesting depth in config
- Visual indicators for nesting level
- Prevent drag-drop beyond max depth
- Validation on block insertion

**Files to Modify:**
- `config/dropblockeditor.php`
- `src/Components/DropBlockEditor.php`
- `resources/js/editor.js`
- `resources/views/editor.blade.php`

---

### 2.3 Grid System Integration
**Goal:** CSS Grid/Flexbox helpers for layouts

**Implementation:**
- Grid configuration in blocks
- Responsive grid classes
- Gap and spacing controls
- Alignment options

**Files to Create:**
- `src/Layouts/GridLayout.php`
- `src/Layouts/FlexLayout.php`

**Files to Modify:**
- `src/Parsers/Html.php`
- `resources/css/editor.css`

---

### 2.4 Layout Presets
**Goal:** One-click layout insertion

**Implementation:**
- Predefined layout blocks
- Hero section, feature grid, testimonial slider, etc.
- Category-based organization
- Custom layout creation

**Files to Create:**
- `src/Blocks/Layouts/HeroSection.php`
- `src/Blocks/Layouts/FeatureGrid.php`
- `src/Blocks/Layouts/TestimonialSlider.php`

---

## PHASE 3: Responsive Features

**Priority:** HIGH  
**Status:** Not Started  
**Goal:** True responsive editing for websites

### 3.1 Per-Block Breakpoint Settings
**Goal:** Device-specific block configuration

**Features:**
- Visibility: show/hide on mobile/tablet/desktop
- Spacing adjustments per breakpoint
- Width/alignment overrides
- Font size scaling

**Implementation:**
```php
public function schema(): BlockSchema {
    return BlockSchema::make()
        ->string('title', 'Title')
        ->responsive() // Enable responsive options
        ->spacing('padding', 'Padding')
        ->visibility('visibility', 'Show On');
}
```

**Files to Create:**
- `src/Responsive/Breakpoint.php`
- `src/Responsive/ResponsiveValue.php`
- `src/Schemas/FieldTypes/ResponsiveField.php`

**Files to Modify:**
- `src/Parsers/Html.php` (generate responsive CSS)
- `src/Components/DropBlockEditor.php`
- `resources/views/editor.blade.php`
- `resources/js/editor.js`

---

### 3.2 Enhanced Preview System
**Goal:** Better device simulation

**Features:**
- True device simulation (user agents)
- Multiple device frames side-by-side
- Custom breakpoint definitions
- Touch event simulation

**Files to Modify:**
- `resources/views/editor.blade.php`
- `resources/js/editor.js`
- `resources/css/editor.css`

---

### 3.3 Responsive Image Support
**Goal:** Optimized images for all devices

**Features:**
- Srcset generation
- Art direction (different images per breakpoint)
- Lazy loading options
- WebP/AVIF support

**Files to Create:**
- `src/Images/ResponsiveImage.php`
- `src/Schemas/FieldTypes/ResponsiveImageField.php`

---

## PHASE 4: Block Variations & Templates

**Priority:** MEDIUM  
**Status:** Not Started  
**Goal:** Faster content creation

### 4.1 Block Style Variants
**Goal:** Predefined style variations per block

**Implementation:**
```php
public function variants(): array
{
    return [
        'default' => ['class' => 'bg-white'],
        'dark' => ['class' => 'bg-gray-900 text-white'],
        'accent' => ['class' => 'bg-blue-500 text-white'],
    ];
}
```

**Files to Modify:**
- `src/Blocks/Block.php`
- `resources/views/editor.blade.php`

---

### 4.2 Template System
**Goal:** Save and reuse editor states

**Features:**
- Save current editor state as template
- Template library with categories
- Import/export templates (JSON)
- Preview thumbnails

**Files to Create:**
- `src/Templates/Template.php`
- `src/Templates/TemplateRepository.php`
- `src/Commands/SaveTemplateCommand.php`
- `src/Commands/LoadTemplateCommand.php`

---

### 4.3 Block Presets
**Goal:** Pre-filled content blocks

**Features:**
- Industry-specific templates
- Marketing-focused presets
- E-commerce blocks
- Portfolio layouts

---

## PHASE 5: Persistence & Import/Export

**Priority:** MEDIUM  
**Status:** Not Started  
**Goal:** Production-ready storage

### 5.1 Schema Versioning
**Goal:** Handle breaking changes gracefully

**Features:**
- Version numbers in block data
- Migration system for breaking changes
- Backwards compatibility layer
- Upgrade/downgrade paths

**Files to Create:**
- `src/Migrations/BlockMigration.php`
- `src/Migrations/MigrationManager.php`

---

### 5.2 Storage Adapters
**Goal:** Flexible storage options

**Adapters:**
- Database (Eloquent)
- File-based (JSON)
- Cache layer for performance
- Cloud storage (S3, etc.)

**Files to Create:**
- `src/Storage/StorageInterface.php`
- `src/Storage/DatabaseStorage.php`
- `src/Storage/FileStorage.php`
- `src/Storage/CacheStorage.php`

---

### 5.3 Export Formats
**Goal:** Multiple output formats

**Formats:**
- JSON (full data)
- Static HTML (deployment-ready)
- PDF generation
- Screenshot/image export
- AMP version

**Files to Create:**
- `src/Exporters/HtmlExporter.php`
- `src/Exporters/PdfExporter.php`
- `src/Exporters/ImageExporter.php`

---

## PHASE 6: Advanced Features

**Priority:** LOW  
**Status:** Not Started  
**Goal:** Power-user features

### 6.1 Conditional Blocks
**Goal:** Dynamic visibility based on conditions

**Features:**
- Show/hide based on data conditions
- Time-based visibility
- User-role based content
- A/B testing support

---

### 6.2 Dynamic Data Binding
**Goal:** Live data integration

**Features:**
- Database query blocks
- API integration
- Live data refresh
- Webhook triggers

---

### 6.3 Performance Optimizations
**Goal:** Production performance

**Features:**
- Lazy loading for heavy blocks
- Asset optimization
- CDN integration helpers
- Caching strategies

---

### 6.4 Collaborative Editing Prep
**Goal:** Foundation for real-time collaboration

**Features:**
- Operational transform preparation
- WebSocket event structure
- Conflict resolution basics
- User presence indicators

---

## Development Workflow

### Starting a Phase
1. Create feature branch: `git checkout -b phase-X-feature-name`
2. Update this TASKS.md with "Status: In Progress"
3. Implement changes incrementally
4. Add tests for each change
5. Run full test suite before completing

### Testing Checklist for Each Phase
- [ ] All existing tests pass
- [ ] New tests added for features
- [ ] PHPStan analysis passes
- [ ] Manual testing in browser
- [ ] Documentation updated (if applicable)

### Completion Criteria
- [ ] All items in phase completed
- [ ] Tests passing
- [ ] Code reviewed
- [ ] Merged to main branch
- [ ] TASKS.md updated with "Status: Completed"

---

## Quick Reference: Files Structure

```
config/
  dropblockeditor.php          # Configuration

src/
  Blocks/
    Block.php                   # Base block class
    ContainerBlock.php          # (PHASE 2) Nesting support
    Example.php                 # Example block
  Components/
    BlockEditComponent.php      # Edit component base
    DropBlockEditor.php         # Main editor component
    Example.php                 # Example edit component
  Contracts/
    BlockInterface.php          # (PHASE 1) Block contract
  Exceptions/
    BlockNotFoundException.php  # (PHASE 1) Error handling
    ValidationException.php     # (PHASE 1) Validation errors
  Exporters/                    # (PHASE 5) Export formats
  Layouts/                      # (PHASE 2) Grid/flex layouts
  Migrations/                   # (PHASE 5) Schema migrations
  Parsers/
    Editor.php                  # Editor parser
    Html.php                    # HTML parser
    Mjml.php                    # MJML parser
    Parse.php                   # Parser runner
    Parser.php                  # Base parser
  Responsive/                   # (PHASE 3) Responsive utilities
  Schemas/                      # (PHASE 1) Block schemas
  Slots/                        # (PHASE 2) Container slots
  Storage/                      # (PHASE 5) Storage adapters
  Templates/                    # (PHASE 4) Template system

resources/
  css/
    editor.css                  # Editor styles
  js/
    editor.js                   # Drag-drop logic
  views/
    base.blade.php              # Base template
    editor.blade.php            # Editor UI

tests/
  EditorTest.php                # Main test suite
```

---

## Notes

- **Dependencies:** Each phase builds on previous phases
- **Breaking Changes:** Document any breaking changes in CHANGELOG.md
- **Documentation:** Update docs at dropblockeditor.com when features are stable
- **Versioning:** Consider semantic versioning after Phase 1 completion

---

*Last Updated: 2026-02-06*  
*Current Phase: Not Started*