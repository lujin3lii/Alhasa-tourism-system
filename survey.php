<?php
require_once __DIR__ . '/trip-common.php';
trip_require_tourist();

/* Display errors returned from generate-itinerary.php */
$tripError = $_SESSION['trip_error'] ?? '';
unset($_SESSION['trip_error']);

if ($tripError === '' && isset($_GET['error'])) {
    $tripError = (string)$_GET['error'];
}

$questions = [];
$optionsByQuestion = [];

/* Load questions from database */
$sql = "
    SELECT Question_ID, Question_Text, Question_Type,
           Is_Required, Display_Order
    FROM survey_questions
    ORDER BY Display_Order ASC
";

$result = $conn->query($sql);

if (!$result) {
    die("Survey questions error: " . $conn->error);
}

while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}

/* Load options from database */
$sql = "
    SELECT Option_ID, Question_ID, Option_Text,
           Parent_Option_ID, Display_Order
    FROM survey_options
    ORDER BY Display_Order ASC
";

$result = $conn->query($sql);

if (!$result) {
    die("Survey options error: " . $conn->error);
}

while ($row = $result->fetch_assoc()) {
    $questionId = (int)$row['Question_ID'];
    $parentId = (int)($row['Parent_Option_ID'] ?? 0);

    $optionsByQuestion[$questionId][$parentId][] = $row;
}

/* Render options and nested options */
if (!function_exists('renderSurveyOptions')) {
    function renderSurveyOptions($questionId, $parentId, $options, $inputType) {

        if (empty($options[$parentId])) {
            return;
        }

        foreach ($options[$parentId] as $option) {

            $optionId = (int)$option['Option_ID'];
            $hasChildren = !empty($options[$optionId]);

            $inputName = $inputType === 'checkbox'
                ? "answers[$questionId][]"
                : "answers[$questionId]";

            ?>

            <label class="choice-tile">
                <input
                    type="<?= trip_h($inputType) ?>"
                    name="<?= trip_h($inputName) ?>"
                    value="<?= $optionId ?>"
                    data-option-id="<?= $optionId ?>"
                >

                <span class="choice-main">
                    <?= trip_h($option['Option_Text']) ?>
                </span>
            </label>

            <?php if ($hasChildren): ?>

                <div class="survey-children"
                     data-parent-option="<?= $optionId ?>"
                     hidden>

                    <?php
                    renderSurveyOptions(
                        $questionId,
                        $optionId,
                        $options,
                        $inputType
                    );
                    ?>

                </div>

            <?php endif; ?>

            <?php
        }
    }
}

trip_page_header('survey');
?>

<section class="trip-page survey-page">

    <div class="survey-intro">

        <a class="back-link" href="itinerary.php">
            ← Back to trip planner
        </a>

        <span class="eyebrow">
            YOUR PERSONAL TRIP DESIGNER
        </span>

        <h1>Let's get to know your kind of trip.</h1>

        <p>
            Answer a few questions. Our AI will use your preferences
            and the available places in Al-Ahsa to create your itinerary.
        </p>

        <div class="survey-progress">
            <span></span>
        </div>

        <small>
            One questionnaire · Personalized AI recommendations
        </small>

    </div>

    <?php if ($tripError !== ''): ?>

        <div class="trip-error" role="alert">
            <?= trip_h($tripError) ?>
        </div>

    <?php endif; ?>

    <?php if (empty($questions)): ?>

        <div class="trip-error">
            No survey questions are available.
        </div>

    <?php else: ?>

        <form
            class="trip-form"
            id="surveyForm"
            action="generate-itinerary.php"
            method="POST"
        >

            <?php foreach ($questions as $index => $question): ?>

                <?php

                $questionId = (int)$question['Question_ID'];

                $questionType = strtolower(
                    trim($question['Question_Type'] ?? '')
                );

                /*
                 * Multiple-choice questions allow several answers.
                 * All other question types allow one answer only.
                 */
                $inputType = (
                    strpos($questionType, 'multiple') !== false ||
                    strpos($questionType, 'checkbox') !== false ||
                    strpos($questionType, 'multi') !== false
                ) ? 'checkbox' : 'radio';

                $required = (int)$question['Is_Required'] === 1;

                $questionOptions =
                    $optionsByQuestion[$questionId] ?? [];

                ?>

                <section class="question-card survey-question"
                         data-required="<?= $required ? '1' : '0' ?>"
                         data-question-type="<?= trip_h($inputType) ?>">

                    <div class="question-title">

                        <span>
                            <?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?>
                        </span>

                        <div>

                            <h2>
                                <?= trip_h($question['Question_Text']) ?>

                                <?php if ($required): ?>
                                    <span class="survey-required">*</span>
                                <?php endif; ?>

                            </h2>

                            <p>
                                <?= $inputType === 'checkbox'
                                    ? 'Select all options that apply.'
                                    : 'Choose one option only.' ?>
                            </p>

                        </div>

                    </div>

                    <?php if (empty($questionOptions[0])): ?>

                        <p>No options available for this question.</p>

                    <?php else: ?>

                        <div class="choice-grid">

                            <?php
                            renderSurveyOptions(
                                $questionId,
                                0,
                                $questionOptions,
                                $inputType
                            );
                            ?>

                        </div>

                    <?php endif; ?>

                </section>

            <?php endforeach; ?>

            <div class="form-actions">

                <a class="trip-secondary-btn" href="itinerary.php">
                    Back
                </a>

                <button class="trip-primary-btn" type="submit">
                    Generate My AI Itinerary
                    <span>✦</span>
                </button>

            </div>

            <p class="form-footnote">
                Your itinerary will be generated using the actual
                published places available in our database.
            </p>

        </form>

    <?php endif; ?>

</section>

<style>
.survey-required {
    color: #b54747;
}

.survey-question .choice-grid {
    margin-top: 20px;
}

.survey-question .choice-tile {
    cursor: pointer;
}

.survey-question .choice-tile input {
    accent-color: #075b45;
}

.survey-children {
    grid-column: 1 / -1;
    margin: 8px 0 12px 22px;
    padding: 12px 0 12px 18px;
    border-left: 2px solid #8ab7a0;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.survey-children[hidden] {
    display: none !important;
}

.survey-validation-error {
    color: #b54747;
    margin-top: 12px;
}

@media (max-width: 650px) {
    .survey-children {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('surveyForm');

    if (!form) return;

    /* Show or hide child options according to parent selection. */
    function updateChildOptions() {

        form.querySelectorAll('.survey-children').forEach(function (group) {

            const parentId = group.dataset.parentOption;

            const parentInput = form.querySelector(
                'input[data-option-id="' + parentId + '"]'
            );

            const selected = parentInput && parentInput.checked;

            group.hidden = !selected;

            if (!selected) {

                group.querySelectorAll('input').forEach(function (input) {
                    input.checked = false;
                });

            }

        });

    }

    /* Update nested options whenever an answer changes. */
    form.addEventListener('change', function () {
        updateChildOptions();
    });

    /* Validate required questions before submitting. */
    form.addEventListener('submit', function (event) {

        updateChildOptions();

        form.querySelectorAll('.survey-validation-error')
            .forEach(function (item) {
                item.remove();
            });

        let firstMissing = null;

        form.querySelectorAll(
            '.survey-question[data-required="1"]'
        ).forEach(function (question) {

            const checkedInput = question.querySelector(
                'input:checked'
            );

            if (!checkedInput && !firstMissing) {
                firstMissing = question;
            }

        });

        if (firstMissing) {

            event.preventDefault();

            const error = document.createElement('p');

            error.className = 'survey-validation-error';
            error.textContent = 'Please answer this required question.';

            firstMissing.appendChild(error);

            firstMissing.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

        }

    });

    updateChildOptions();

});
</script>

<?php trip_page_footer(); ?>