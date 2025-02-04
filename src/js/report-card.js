jQuery(document).ready(function($) {
    const container = $('.report-card-container');
    if (!container.length) return;

    const userId = container.data('user-id');
    const type = container.data('type');
    const classId = container.data('class-id');

    function createGradeTable(grades, type) {
        const $table = $('<table>').addClass('grade-table');
        
        // Create header
        const headers = ['Date', 'Assignment'];
        if (type === 'all') {
            headers.push('Type', 'Percentage', 'Letter Grade');
        } else {
            headers.push('Possible Points', 'Points Earned', 'Percentage');
        }

        const $thead = $('<thead>').append(
            $('<tr>').append(
                headers.map(text => 
                    $('<th>').addClass('grade-table-header-cell').text(text)
                )
            )
        );

        // Create rows
        const $tbody = $('<tbody>');
        grades.forEach(grade => {
            const percentage = grade.score === '' ? 'IOU' : Math.round((parseFloat(grade.score) / parseFloat(grade.total)) * 100);
            const percentageText = percentage === 'IOU' ? '--' : percentage + '%';
            
            const $row = $('<tr>').addClass('grade-row')
                .append($('<td>').addClass('grade-date').text(grade.date))
                .append($('<td>').addClass('grade-name').text(grade.name));

            if (type === 'all') {
                $row.append(
                    $('<td>').addClass('grade-type').append(
                        $('<a>').attr('href', grade.type).text(grade.type)
                    ),
                    $('<td>').addClass('grade-percentage').text(percentageText),
                    $('<td>').addClass('grade-letter').text(getLetterGrade(percentage))
                );
            } else {
                $row.append(
                    $('<td>').addClass('grade-total').text(grade.total),
                    $('<td>').addClass('grade-score').text(processScore(grade.score)),
                    $('<td>').addClass('grade-percentage').text(percentageText)
                );
            }

            $tbody.append($row);
        });

        return $table.append($thead, $tbody);
    }

    function getLetterGrade(percentage) {
        if (percentage === 'IOU') return '--';
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

    $.ajax({
        url: tigerGradesData.apiUrl,
        method: 'GET',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', tigerGradesData.nonce);
        },
        data: {
            user_id: userId,
            type: type,
            class_id: classId
        },
        success: function(data) {
            // Clear loading message
            container.empty();
            
            // Add student name
            container.append(
                $('<div>').addClass('student-name-container').append(
                    $('<div>').addClass('student-name').text(data.name)
                )
            );

            // Add average section
            const $avgContainer = $('<div>').addClass('average-container');
            
            if (type === 'all') {
                $avgContainer.append(
                    $('<h4>').addClass('average').text(`Overall Grade: ${data.avg.final}`),
                    $('<h4>').addClass('average').text(`Letter Grade: ${getLetterGrade(parseFloat(data.avg.final))}`)
                );
            } else {
                $avgContainer.append(
                    $('<h4>').addClass('average').text(`Average: ${data.avg[type]}`)
                );
            }
            
            container.append($avgContainer);

            // Add grades table
            container.append(createGradeTable(data.grades, type));
        },
        error: function(xhr, status, error) {
            container.html('<div class="error-message">Error loading report card. Please try again later.</div>');
            console.error('Error fetching report card:', error);
        }
    });
}); 