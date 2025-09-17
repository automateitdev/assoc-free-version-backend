<!DOCTYPE html>
<html>

<head>
    <title>Student Information</title>
    <style>
        @page {
            size: legal landscape;
            margin: 10mm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
        }

        th, td {
            border: 1px solid black;
            padding: 1mm;
            text-align: left;
            page-break-inside: avoid !important;
        }

        tr {
            page-break-inside: avoid !important;
            page-break-after: auto;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }
    </style>
</head>

<body>
    <div style="text-align: center;">
        <h3>{{ $institute_name }}</h3>
        <p>{{ $institute_address }}</p>
    </div>

    @foreach ($data->chunk(10) as $chunk)
    <table>
        <thead>
            <tr>
                <th>SL.</th>
                <th>SSC Board</th>
                <th>SSC Passing Year</th>
                <th>SSC Roll</th>
                <th>Applicant's Name, Father's Name and Mother's Name</th>
                <th>Class Roll, Guardian Name</th>
                <th>Student's Picture</th>
                <th>Address</th>
                <th>Student Mobile</th>
                <th>Date of Birth</th>
                <th>Religion, Gender</th>
                <th>Subject Code</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($chunk as $index => $config)
                <tr>
                    <td>{{ $loop->parent->iteration * 10 + $loop->iteration - 10 }}</td>
                    <td>{{ isset($config->board) ? $config->board : 'N/A' }}</td>
                    <td>{{ isset($config->passing_year) ? $config->passing_year : 'N/A' }}</td>
                    <td>{{ isset($config->roll) ? $config->roll : 'N/A' }}</td>
                    <td>{{ $config->student_name_english }}, {{ $config->father_name_english }},
                        {{ $config->mother_name_english }}</td>
                    <td>{{ $config->assigned_roll }}, {{ $config->guardian_name }}</td>
                    <td>
                        @if (!empty($config->student_pic) && file_exists(storage_path('app/public/' . $config->student_pic)))
                            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(storage_path('app/public/' . $config->student_pic))) }}"
                                alt="Student's Picture" style="max-width: 40px; max-height: 40px;">
                        @else
                            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('default_image.png'))) }}"
                                alt="Default Picture" style="max-width: 60px; max-height: 60px;">
                        @endif
                    </td>
                    <td>
                        @php
                            $permanentDivision = json_decode($config->permanent_division, true);
                            $permanentDistrict = json_decode($config->permanent_district, true);
                            $permanentUpozilla = json_decode($config->permanent_upozilla, true);
                            $divisionName = $permanentDivision['name'] ?? '';
                            $districtName = $permanentDistrict['name'] ?? '';
                            $upozillaName = $permanentUpozilla['name'] ?? '';
                        @endphp
                        {{ $config->permanent_address . ', ' . $divisionName . ', ' . $districtName . ', ' . $upozillaName . ', ' . $config->permanent_post_office . ', ' . $config->permanent_post_code }}
                    </td>
                    <td>{{ $config->student_mobile }}</td>
                    <td>{{ \Carbon\Carbon::parse($config->date_of_birth)->format('d-m-Y') }}</td>
                    <td>{{ $config->religion }}, {{ $config->gender }}</td>
                    <td style="width:auto; white-space: nowrap;">
                        @php
                            $subjectData = json_decode($config->subject, true);
                            $compulsorySubjects = $subjectData['compulsory'];
                            $groupSubjects = $subjectData['group'];
                            $optionalSubjects = $subjectData['optional'];
                        @endphp
                        {{ implode(', ', $compulsorySubjects) }}<br>
                        @if (count($groupSubjects) > 0)
                            {{ implode(', ', $groupSubjects) }}<br>
                        @endif
                        @if (count($optionalSubjects) > 0)
                            {{ implode(', ', $optionalSubjects) }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @if (!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif
    @endforeach
</body>

</html>
