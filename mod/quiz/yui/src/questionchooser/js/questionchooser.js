var SELECTORS = {
    ADDNEWQUESTIONBUTTON: 'a.addquestion',
    ADDNEWQUESTIONFORM: 'form.addnewquestion',
    CREATENEWQUESTION: 'div.createnewquestion',
    CREATENEWQUESTIONFORM: 'div.createnewquestion form',
    CHOOSERDIALOGUE: 'div.chooserdialogue',
    CHOOSERHEADER: 'div.choosertitle'
};

function QuestionChooser() {
    QuestionChooser.superclass.constructor.apply(this, arguments);
}

Y.extend(QuestionChooser, M.core.chooserdialogue, {
    initializer: function() {
        Y.all(SELECTORS.ADDNEWQUESTIONBUTTON).each(function(node) {
                node.on('click', this.displayQuestionChooser, this);
        }, this);
    },
    displayQuestionChooser: function(e) {
        e.preventDefault();
        var dialogue = Y.one(SELECTORS.CREATENEWQUESTION + ' ' + SELECTORS.CHOOSERDIALOGUE),
            header = Y.one(SELECTORS.CREATENEWQUESTION + ' ' + SELECTORS.CHOOSERHEADER);

        if (this.container === null) {
            // Setup the dialogue, and then prepare the chooser if it's not already been set up.
            this.setup_chooser_dialogue(dialogue, header, {});
            this.prepare_chooser();
        }

        // Update all of the hidden fields within the questionbank form.
        var originForm = e.target.ancestor(Y.Moodle.mod_quiz.util.page.SELECTORS.PAGE, true).one(SELECTORS.ADDNEWQUESTIONFORM),
            targetForm = this.container.one('form'),
            hiddenElements = originForm.all('input[type="hidden"]');

        targetForm.all('input.customfield').remove();
        hiddenElements.each(function(field) {
            targetForm.appendChild(field.cloneNode())
                .removeAttribute('id')
                .addClass('customfield');
        });

        // Display the chooser dialogue.
        this.display_chooser(e);
        // var test;
    }
}, {
    NAME: 'quizQuestionChooser'
});

M.mod_quiz = M.mod_quiz || {};
M.mod_quiz.init_questionchooser = function(config) {
    M.mod_quiz.question_chooser = new QuestionChooser(config);
    return M.mod_quiz.question_chooser;
};
