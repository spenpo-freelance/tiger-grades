jQuery(document).ready(function($) {
    const container = $('.report-card-container');
    if (!container.length) return;
    container.empty();

    const userId = container.data('user-id');
    const type = container.data('type');
    const enrollmentId = container.data('enrollment-id');
    const isTeacher = !!container.data('is-teacher');

    container.append($('<div>').addClass('student-name-container'));
    container.append($('<div>').addClass('class-metadata-container'));
    const reportCard = $('<div>').addClass(`report-card`);
    reportCard.append($('<div>').addClass('loading'));
    container.append(reportCard);

    // Separate table header creation for reusability
    function createTableHeader(type) {
        const headers = ['Date', 'Task'];
        if (type === 'all') {
            headers.push('Type', 'Percent', 'Grade');
        } else {
            headers.push('Max', 'Earned', 'Percent');
        }

        return $('<thead>').append(
            $('<tr>').append(
                headers.map(text => 
                    $('<th>').addClass('grade-table-header-cell').text(text)
                )
            )
        );
    }

    function createEmptyTableBody() {
        return $('<tbody>').append(
            $('<tr>').append(
                $('<td>')
                    .attr('colspan', '5')
                    .addClass('empty-state-message')
                    .text('No grades found')
            )
        );
    }

    function createGradeTable(type, isEmpty = false) {
        const table = $('<table>').addClass('grade-table');
        table.append(createTableHeader(type));
        
        if (isEmpty) {
            table.append(createEmptyTableBody());
        }
        
        return table;
    }

    function fillGradeTable(table, grades, type) {
        table.find('tbody').remove();
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

        return table.append(tbody);
    }

    // 1. UI Controls Component
    function createReportControls(data, type, isFirstRender = false) {
        const controls = $('<div>').addClass('report-card-controls');
        // Export all students report card
        if (isTeacher && type === 'all') {
            const exportAllButton = $('<button>')
                .addClass('export-pdf-button btn btn-theme-primary btn-md')
                .text('Export All')
                .on('click', () => exportReportCardAsPDF(data, type, true));
            controls.append(exportAllButton);
        }

        if (isFirstRender) return controls;

        // Export single student report card
        const exportButton = $('<button>')
            .addClass('export-pdf-button btn btn-theme-primary btn-md')
            .text('Export as PDF')
            .on('click', () => exportReportCardAsPDF(data, type));
        
        controls.append(exportButton);

        return controls;
    }

    // 2. Grade Summary Component
    function createGradeSummary(data, type, isFirstRender = false) {
        const studentInfo = $('<div>').addClass('average-container');

        if (isFirstRender) {
            studentInfo.append($('<h4>').addClass('average').text('Please select a student to view their grades'));
            return studentInfo;
        }
        
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
        
        return studentInfo;
    }

    // 3. Main Report Card Renderer
    function renderReportCard(data, table, header, isFirstRender = false) {
        // Create components
        const studentInfo = createGradeSummary(data, type, isFirstRender);
        const controls = createReportControls(data, type, isFirstRender);
        
        // Assemble header
        header.empty()
            .append(studentInfo)
            .append(controls);

        // Create and append grade table
        const tableContainer = $('<div>').addClass('grade-table-container');
        
        if (isFirstRender) {
            // For teachers before student selection
            table.find('tbody').replaceWith(createEmptyTableBody());
            tableContainer.append(table);
        } else {
            tableContainer.append(fillGradeTable(table, data.grades, type));
        }
        
        reportCard.append(tableContainer);
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

    $.ajax({
        url: tigerGradesData.apiUrl,
        method: 'GET',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', tigerGradesData.nonce);
        },
        data: {
            user_id: userId,
            type,
            enrollment_id: enrollmentId,
            is_teacher: isTeacher,
        },
        success: function(data) {
            reportCard.empty();

            // Header flex container
            const header = $('<div>').addClass('report-card-header');
            reportCard.append(header);

            // Create initial table - empty for teachers, populated for students
            const gradeTable = createGradeTable(type, isTeacher);

            // Add student name
            const studentName = $('.student-name-container');
            if (studentName.children().length === 0) {
                if (isTeacher) {    
                    studentName.append(
                        $('<select>').addClass('student-name-select').append(
                            $('<option>').text('Select Student').attr('value', '').attr('disabled', 'disabled').attr('selected', 'selected'),
                            data.reports.map(({ name, student_id }) => $('<option>').text(name).attr('value', student_id))
                        ).on('change', function() {
                            const selectedStudentId = $(this).val();
                            const studentData = data.reports.find(({ student_id }) => student_id === selectedStudentId);
                            renderReportCard({ ...data, ...studentData }, gradeTable, header);
                        })
                    );
                    // Initial render with empty state for teachers
                    renderReportCard(data, gradeTable, header, true);
                } else {
                    studentName.append(
                        $('<div>').addClass('student-name').text(data.name)
                    );
                    renderReportCard(data, gradeTable, header);
                }
            }
        },
        error: function(xhr, status, error) {
            reportCard.html('<div class="error-message">Error loading report card. Please try again later.</div>');
        }
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
                enrollment_id: enrollmentId,
                is_teacher: isTeacher,
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
    function exportReportCardAsPDF(data, type, isAll = false) {
        // Load jsPDF library dynamically
        if (typeof jspdf === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
            script.onload = function() {
                const tableScript = document.createElement('script');
                tableScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js';
                tableScript.onload = function() {
                    isAll ? generateAllStudentsPDF(data) : generatePDF(data, type);
                };
                document.head.appendChild(tableScript);
            };
            document.head.appendChild(script);
        } else {
            isAll ? generateAllStudentsPDF(data) : generatePDF(data, type);
        }
    }

    function setupDocument(doc, yPos, data, type = null) {
        // Generate title components
        const studentName = data.name;
        const className = data.class || 'Class'; // Fallback if class_name isn't available
        
        // Set title
        let title = `${studentName}'s ${className}`;
        
        if (type) title += ` ${
            type === 'all' ? 'Report Card' : `${type.charAt(0).toUpperCase() + type.slice(1)} Grades`
        }`;

        doc.setFontSize(16);
        doc.text(title, 14, 20);
        
        // Add metadata
        doc.setFontSize(12);
        doc.text(`Teacher: ${data.teacher}`, 14, yPos);
        
        // Create table data
        let tableColumn;
        switch (type) {
            case 'all':
                tableColumn = ['Date', 'Task', 'Type', 'Percent', 'Grade'];
                break;
            case null:
                tableColumn = ['Date', 'Task', 'Type', 'Max', 'Earned', 'Percent', 'Grade'];
                break;
            default:
                tableColumn = ['Date', 'Task', 'Max', 'Earned', 'Percent'];
                break;
        }
        
        return { tableColumn };
    }

    function generateAllStudentsPDF(data) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        data.reports.forEach((report, index) => {
            // Start a new page for each student (except the first one)
            if (index > 0) {
                doc.addPage();
            }

            // Reset yPos for each new student
            let yPos = 30;

            const { tableColumn } = setupDocument(doc, yPos, { ...data, ...report });
            yPos += 7;

            // Add grade information
            doc.text(`Overall Grade: ${report.avg.final}`, 14, yPos);
            yPos += 7;
            doc.text(`Letter Grade: ${getLetterGrade(parseFloat(report.avg.final))}`, 14, yPos);
            yPos += 7;
            Object.entries(report.avg).forEach(([key, value]) => {
                if (key === 'final') return;
                doc.text(`${key.charAt(0).toUpperCase() + key.slice(1)} Average: ${value}`, 14, yPos);
                yPos += 7;
            });
            
            const tableRows = [];
            
            // Ensure we're accessing the grades array correctly
            const grades = Array.isArray(report.grades) ? report.grades : [];
            
            grades.forEach(grade => {
                const percentage = getPercentage(grade.score, grade.total);
                const percentageText = `${percentage}${typeof percentage === 'number' ? '%' : ''}`;
                
                tableRows.push([
                    grade.date,
                    grade.name,
                    grade.type_label || grade.type, // Fallback to type if type_label isn't available
                    grade.total,
                    processScore(grade.score),
                    percentageText,
                    getLetterGrade(percentage)
                ]);
            });

            generateTable(doc, yPos, tableColumn, tableRows);
        });

        addFooter(doc);

        // Generate filename
        const filename = `${data.class.replace(/\s+/g, '_')}_Reports.pdf`;
        
        // Save the PDF
        doc.save(filename);
    }

    function generateTable(doc, yPos, tableColumn, tableRows) {
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
    }
    
    function addFooter(doc) {
        // Add footer with timestamp
        const now = new Date();
        const timestamp = `Generated on: ${now.toLocaleDateString()} at ${now.toLocaleTimeString()}`;
        doc.setFontSize(10);
        doc.text(timestamp, 14, doc.internal.pageSize.height - 10);
    }

    function generatePDF(data, type) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        let yPos = 30;
        
        const { tableColumn } = setupDocument(doc, yPos, data, type);
        
        // Add grade information
        if (type === 'all') {
            doc.text(`Overall Grade: ${data.avg.final}`, 14, yPos);
            yPos += 7;
            doc.text(`Letter Grade: ${getLetterGrade(parseFloat(data.avg.final))}`, 14, yPos);
        } else {
            doc.text(`${type.charAt(0).toUpperCase() + type.slice(1)} Average: ${data.avg[type]}`, 14, yPos);
        }
        yPos += 7;
        
        const tableRows = [];
        
        // Ensure we're accessing the grades array correctly
        const grades = Array.isArray(data.grades) ? data.grades : [];
        
        grades.forEach(grade => {
            const percentage = getPercentage(grade.score, grade.total);
            const percentageText = `${percentage}${typeof percentage === 'number' ? '%' : ''}`;
            
            if (type === 'all') {
                tableRows.push([
                    grade.date,
                    grade.name,
                    grade.type_label || grade.type, // Fallback to type if type_label isn't available
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

        generateTable(doc, yPos, tableColumn, tableRows);
        addFooter(doc);

        // Generate filename
        const sanitizedStudentName = studentName.replace(/\s+/g, '_');
        const sanitizedClassName = className.replace(/\s+/g, '_');
        const filename = `${sanitizedStudentName}_${sanitizedClassName}_${
            type === 'all' ? 'Report_Card' : `${type.charAt(0).toUpperCase() + type.slice(1)}_Grades`
        }.pdf`;
        
        // Save the PDF
        doc.save(filename);
    }
}); 