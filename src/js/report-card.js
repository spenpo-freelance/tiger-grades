jQuery(document).ready(function($) {
    const container = $('.report-card-container');
    if (!container.length) return;
    container.empty();

    const userId = container.data('user-id');
    const type = container.data('type');
    const classId = container.data('class-id');
    const semester = String(container.data('semester')).split(', ');

    container.append($('<div>').addClass('student-name-container'));

    // loop through semesters, create containers with loading divs, create tabs if more than one semester
    // on click, show the container and hide the others
    if (semester.length > 1) {
        const tabs = $('<div>').addClass('tabs');
        semester.forEach((sem, idx, arr) => {
            const tab = $('<button>').addClass(`tab tab-${sem} ${idx === 0 ? 'active' : ''}`).text(`Semester ${sem}`);
            tab.on('click', () => {
                $('.tab.active').removeClass('active');
                $(`.tab-${sem}`).addClass('active');
                arr.forEach((s, i) => {
                    if (i === idx) {
                        $(`.report-card-${s}`).show();
                    } else {
                        $(`.report-card-${s}`).hide();
                    }
                });
            });
            tabs.append(tab);
        });
        container.append(tabs);
    }

    semester.forEach((sem, idx) => {
        const reportCard = $('<div>').addClass(`report-card-${sem}`);
        if (idx > 0) {
            reportCard.hide();
        }
        reportCard.append($('<div>').addClass('loading'));
        container.append(reportCard);
    });

    function createGradeTable(grades, type) {
        const table = $('<table>').addClass('grade-table');
        
        // Create header
        const headers = ['Date', 'Assignment'];
        if (type === 'all') {
            headers.push('Type', 'Percentage', 'Letter Grade');
        } else {
            headers.push('Possible Points', 'Points Earned', 'Percentage');
        }

        const thead = $('<thead>').append(
            $('<tr>').append(
                headers.map(text => 
                    $('<th>').addClass('grade-table-header-cell').text(text)
                )
            )
        );

        // Create rows
        const tbody = $('<tbody>');
        grades.forEach(grade => {
            const percentage = getPercentage(grade.score, grade.total);
            const percentageText = `${percentage}${typeof percentage === 'number' ? '%' : ''}`;
            
            const row = $('<tr>').addClass('grade-row')
                .append($('<td>').addClass('grade-date').text(grade.date))
                .append($('<td>').addClass('grade-name').text(grade.name));

            if (type === 'all') {
                row.append(
                    $('<td>').addClass('grade-type').append(
                        $('<a>').attr('href', grade.type).text(grade.type_label)
                    ),
                    $('<td>').addClass('grade-percentage').text(percentageText),
                    $('<td>').addClass('grade-letter').text(getLetterGrade(percentage))
                );
            } else {
                row.append(
                    $('<td>').addClass('grade-total').text(grade.total),
                    $('<td>').addClass('grade-score').text(processScore(grade.score)),
                    $('<td>').addClass('grade-percentage').text(percentageText)
                );
            }

            tbody.append(row);
        });

        return table.append(thead, tbody);
    }

    function getPercentage (score, total) {
        if (score === '') return '--';
        if (score === 'e') return 'EXEMPT';
        else return Math.round((parseFloat(score) / parseFloat(total)) * 100);
    }

    function getLetterGrade(percentage) {
        if (typeof percentage !== 'number') return '--';
        if (percentage >= 90) return 'A';
        if (percentage >= 80) return 'B';
        if (percentage >= 70) return 'C';
        return 'D';
    }

    function processScore(score) {
        if (score === '') return '--';
        if (score === '0') return '00';
        if (score === 'e') return 'EXEMPT';
        return score;
    }

    semester.forEach((sem) => {
        $.ajax({
            url: tigerGradesData.apiUrl,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', tigerGradesData.nonce);
            },
            data: {
                user_id: userId,
                type: type,
                class_id: classId,
                semester: Number(sem)
            },
            success: function(data) {
                const reportCard = $(`.report-card-${sem}`);
                reportCard.empty();

                // Add student name
                const studentName = $('.student-name-container');
                if (studentName.children().length === 0) {
                    studentName.append(
                        $('<div>').addClass('student-name').text(data.name)
                    );
                }

                // Add average section
                const avgContainer = $('<div>').addClass('average-container');
                
                if (type === 'all') {
                    avgContainer.append(
                        $('<h4>').addClass('average').text(`Overall Grade: ${data.avg.final}`),
                        $('<h4>').addClass('average').text(`Letter Grade: ${getLetterGrade(parseFloat(data.avg.final))}`)
                    );
                } else {
                    avgContainer.append(
                        $('<h4>').addClass('average').text(`Semester Average: ${data.avg[type]}`)
                    );
                }
                
                reportCard.append(avgContainer);

                // Add grades table
                reportCard.append(createGradeTable(data.grades, type));
            },
            error: function(xhr, status, error) {
                reportCard.html('<div class="error-message">Error loading report card. Please try again later.</div>');
                console.error('Error fetching report card:', error);
            }
        });
    });
}); 