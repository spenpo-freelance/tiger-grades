jQuery(document).ready(function($) {
    const container = $('.report-card-container');
    if (!container.length) return;
    container.empty();

    const userId = container.data('user-id');
    const type = container.data('type');
    const classId = container.data('class-id');
    const semester = String(container.data('semester')).split(', ');

    container.append($('<div>').addClass('student-name-container'));
    container.append($('<div>').addClass('class-metadata-container'));
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
        const headers = ['Date', 'Task'];
        if (type === 'all') {
            headers.push('Type', 'Percent', 'Grade');
        } else {
            headers.push('Max', 'Earned', 'Percent');
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
                type,
                class_id: classId,
                semester: Number(sem)
            },
            success: function(data) {
                const reportCard = $(`.report-card-${sem}`);
                reportCard.empty();

                // Header flex container
                const header = $('<div>').addClass('report-card-header');
                reportCard.append(header);

                // Info section
                const studentInfo = $('<div>').addClass('average-container');

                // Add student name
                const studentName = $('.student-name-container');
                if (studentName.children().length === 0) {
                    studentName.append(
                        $('<div>').addClass('student-name').text(data.name)
                    );
                }

                const controls = $('<div>').addClass('report-card-controls');

                // Add export button
                const exportButton = $('<button>')
                    .addClass('export-pdf-button btn btn-theme-primary btn-md')
                    .text('Export as PDF')
                    .on('click', function() {
                        exportReportCardAsPDF(data, sem, type);
                    });
                
                controls.append(exportButton);

                if (type === 'all') {
                    studentInfo.append(
                        $('<h4>').addClass('average').text(`Overall Grade: ${data.avg.final}`),
                        $('<h4>').addClass('average').text(`Letter Grade: ${getLetterGrade(parseFloat(data.avg.final))}`)
                    );
                } else {
                    studentInfo.append(
                        $('<h4>').addClass('average').text(`Semester Average: ${data.avg[type]}`)
                    );
                }
                
                header.append(studentInfo);
                header.append(controls);

                // Add grades table
                const tableContainer = $('<div>').addClass('grade-table-container');
                tableContainer.append(createGradeTable(data.grades, type));
                reportCard.append(tableContainer);
            },
            error: function(xhr, status, error) {
                reportCard.html('<div class="error-message">Error loading report card. Please try again later.</div>');
                console.error('Error fetching report card:', error);
            }
        });
    });

    // if it is a specific grade type page, fetch the metadata
    if (type !== 'all') {
        const studentNameContainer = $('.class-metadata-container');
        studentNameContainer.append($('<p>').addClass('metadata text-loading').append(
            $('<strong>').addClass('metadata-type').text(type),
            ` grades are worth `,
            $('<strong>').addClass('metadata-weight').text('(...)'),
            ` of the overall grade`
        ));
        $.ajax({
            url: tigerGradesData.metadataUrl,
            method: 'GET',
            data: {
                type,
                class_id: classId,
                semester: Number(semester[0])
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', tigerGradesData.nonce);
            },
            success: function(data) {
                $('.metadata-weight').text(data.weight);
                $('.metadata-type').text(data.type_label);
                $('.text-loading').removeClass('text-loading');
            },
            error: function(xhr, status, error) {
                console.error('Error fetching metadata:', error);
            }
        });
    }

    // Function to export report card as PDF
    function exportReportCardAsPDF(data, semester, type) {
        // Load jsPDF library dynamically
        if (typeof jspdf === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
            script.onload = function() {
                const tableScript = document.createElement('script');
                tableScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js';
                tableScript.onload = function() {
                    generatePDF(data, semester, type);
                };
                document.head.appendChild(tableScript);
            };
            document.head.appendChild(script);
        } else {
            generatePDF(data, semester, type);
        }
    }

    function generatePDF(data, semester, type) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        const classSentenceCase = classId.split(/[\s_]+/g).reduce((acc, word) => {
            return acc + '_' + word.charAt(0).toUpperCase() + word.slice(1);
        }, '');
        
        // Set title
        const title = `${data.name}'s ${classSentenceCase.replace('_', ' ')} Report Card - Semester ${semester}`;
        doc.setFontSize(16);
        doc.text(title, 14, 20);
        
        // Add average information
        doc.setFontSize(12);
        let yPos = 30;
        
        if (type === 'all') {
            doc.text(`Overall Grade: ${data.avg.final}`, 14, yPos);
            yPos += 7;
            doc.text(`Letter Grade: ${getLetterGrade(parseFloat(data.avg.final))}`, 14, yPos);
        } else {
            doc.text(`${type.charAt(0).toUpperCase() + type.slice(1)} Average: ${data.avg[type]}`, 14, yPos);
        }
        
        // Create table data
        const tableColumn = type === 'all' 
            ? ['Date', 'Assignment', 'Type', 'Percentage', 'Letter Grade']
            : ['Date', 'Assignment', 'Possible Points', 'Points Earned', 'Percentage'];
        
        const tableRows = [];
        
        data.grades.forEach(grade => {
            const percentage = getPercentage(grade.score, grade.total);
            const percentageText = `${percentage}${typeof percentage === 'number' ? '%' : ''}`;
            
            if (type === 'all') {
                tableRows.push([
                    grade.date,
                    grade.name,
                    grade.type_label,
                    percentageText,
                    getLetterGrade(percentage)
                ]);
            } else {
                tableRows.push([
                    grade.date,
                    grade.name,
                    grade.total,
                    processScore(grade.score),
                    percentageText
                ]);
            }
        });
        
        // Generate table
        doc.autoTable({
            head: [tableColumn],
            body: tableRows,
            startY: yPos + 10,
            theme: 'grid',
            styles: {
                fontSize: 10,
                cellPadding: 3
            },
            headStyles: {
                fillColor: [66, 66, 66]
            }
        });
        
        // Generate timestamp
        const now = new Date();
        const timestamp = `Generated on: ${now.toLocaleDateString()} at ${now.toLocaleTimeString()}`;
        doc.setFontSize(10);
        doc.text(timestamp, 14, doc.internal.pageSize.height - 10);
        
        // Save the PDF
        doc.save(`${data.name.replace(/\s+/g, '_')}${classSentenceCase}_Report_Card_S${semester}.pdf`);
    }
}); 
