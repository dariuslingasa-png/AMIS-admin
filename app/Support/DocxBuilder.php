<?php

namespace App\Support;

use App\Models\Student;
use ZipArchive;

class DocxBuilder
{
    /**
     * Generate a native OpenXML .docx file for a given Student.
     *
     * @param Student $student
     * @return string Raw binary content of the .docx file
     */
    public static function buildEnrolmentFormDocx(Student $student): string
    {
        $student->loadMissing(['applicant.user', 'applicant.payment']);
        $applicant = $student->applicant;

        $tempFile = tempnam(sys_get_temp_dir(), 'docx_') . '.docx';

        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Could not create temp docx file.");
        }

        // 1. [Content_Types].xml
        $zip->addFromString('[Content_Types].xml', self::getContentTypesXml());

        // 2. _rels/.rels
        $zip->addFromString('_rels/.rels', self::getRelsXml());

        // 3. word/_rels/document.xml.rels
        $zip->addFromString('word/_rels/document.xml.rels', self::getDocumentRelsXml());

        // 4. word/styles.xml
        $zip->addFromString('word/styles.xml', self::getStylesXml());

        // 5. word/document.xml
        $zip->addFromString('word/document.xml', self::getDocumentXml($student, $applicant));

        $zip->close();

        $binary = file_get_contents($tempFile);
        @unlink($tempFile);

        return $binary;
    }

    private static function getContentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Default Extension="png" ContentType="image/png"/>
    <Default Extension="jpg" ContentType="image/jpeg"/>
    <Default Extension="jpeg" ContentType="image/jpeg"/>
    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
    <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
</Types>';
    }

    private static function getRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>';
    }

    private static function getDocumentRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
    }

    private static function getStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:docDefaults>
        <w:rPrDefault>
            <w:rPr>
                <w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/>
                <w:sz w:val="20"/>
            </w:rPr>
        </w:rPrDefault>
    </w:docDefaults>
</w:styles>';
    }

    private static function getDocumentXml(Student $student, $applicant): string
    {
        $lrn = e($applicant?->lrn ?: ($student->student_number ?: 'NA'));
        $lastName = e(strtoupper((string) ($applicant?->last_name ?: $student->last_name)));
        $firstName = e(strtoupper((string) ($applicant?->first_name ?: $student->first_name)));
        $middleName = e(strtoupper((string) ($applicant?->middle_name ?: $student->middle_name)));
        $sex = e(strtoupper((string) ($applicant?->gender ?: ($student->gender ?: 'N/A'))));
        $gradeLevel = e(strtoupper((string) $student->grade_level));
        
        $rawAddress = $applicant?->address ?: ($applicant?->street_address ?: 'N/A');
        $address = e(strtoupper((string) $rawAddress));
        
        $age = e((string) ($applicant?->age ?: 'N/A'));
        $dob = e($applicant?->date_of_birth ? date('M d, Y', strtotime($applicant->date_of_birth)) : 'N/A');
        $pob = e(strtoupper((string) ($applicant?->place_of_birth ?: 'N/A')));
        $religion = e(strtoupper((string) ($applicant?->religion ?: 'ISLAM')));
        $prevSchool = e(strtoupper((string) ($applicant?->previous_school ?: 'N/A')));
        $prevSchoolAddr = e(strtoupper((string) ($applicant?->previous_school_address ?: 'N/A')));
        $phone = e((string) ($applicant?->contact_number ?: ($applicant?->user?->phone ?: 'N/A')));

        $fatherName = e(strtoupper((string) ($applicant?->father_name ?: 'N/A')));
        $fatherOcc = e(strtoupper((string) ($applicant?->father_occupation ?: 'N/A')));
        $fatherContact = e((string) ($applicant?->father_contact ?: ($applicant?->user?->email ?: 'N/A')));

        $motherName = e(strtoupper((string) ($applicant?->mother_name ?: 'N/A')));
        $motherOcc = e(strtoupper((string) ($applicant?->mother_occupation ?: 'N/A')));
        $motherContact = e((string) ($applicant?->mother_contact ?: 'N/A'));

        $homeAddress = e(strtoupper((string) ($applicant?->home_address ?: $rawAddress)));

        $isNew = ($applicant?->status === 'new') || ($student->created_at && $student->created_at->gt(now()->subDays(30)));
        $newBox = $isNew ? '[X] NEW   [  ] OLD' : '[  ] NEW   [X] OLD';

        $medExplain = e(strtoupper((string) ($applicant?->med_explanation ?: 'NONE')));
        $medMeds = e(strtoupper((string) ($applicant?->current_medications ?: 'NONE')));
        $physician = e(strtoupper((string) ($applicant?->family_physician ?: 'N/A')));
        $physicianPhone = e((string) ($applicant?->physician_phone ?: 'N/A'));
        $emergName = e(strtoupper((string) ($applicant?->emergency_name ?: 'N/A')));
        $emergRel = e(strtoupper((string) ($applicant?->emergency_relationship ?: 'N/A')));
        $emergPhone = e((string) ($applicant?->emergency_phone ?: 'N/A'));
        $referral = e(strtoupper((string) ($applicant?->referral_source ?: 'N/A')));
        $regDate = e($student->created_at ? $student->created_at->format('M d, Y') : date('M d, Y'));

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <!-- Header Title -->
        <w:p>
            <w:pPr><w:jc w:val="center"/></w:pPr>
            <w:r>
                <w:rPr><w:b/><w:sz w:val="28"/><w:color w:val="064E3B"/></w:rPr>
                <w:t>AL MUNAWWARA ISLAMIC SCHOOL</w:t>
            </w:r>
        </w:p>
        <w:p>
            <w:pPr><w:jc w:val="center"/></w:pPr>
            <w:r>
                <w:rPr><w:sz w:val="18"/><w:color w:val="475569"/></w:rPr>
                <w:t>Bugac Ma-a Road, Davao City Philippines</w:t>
            </w:r>
        </w:p>

        <w:p/>

        <w:p>
            <w:pPr><w:jc w:val="center"/></w:pPr>
            <w:r>
                <w:rPr><w:b/><w:sz w:val="24"/></w:rPr>
                <w:t>ENROLMENT APPLICATION FORM</w:t>
            </w:r>
        </w:p>
        <w:p>
            <w:pPr><w:jc w:val="center"/></w:pPr>
            <w:r>
                <w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="059669"/></w:rPr>
                <w:t>SY 2026-2027</w:t>
            </w:r>
        </w:p>

        <w:p>
            <w:pPr><w:jc w:val="right"/></w:pPr>
            <w:r>
                <w:rPr><w:b/><w:sz w:val="20"/></w:rPr>
                <w:t>' . e($newBox) . '</w:t>
            </w:r>
        </w:p>

        <w:p/>

        <!-- Section 1: Student Information -->
        <w:p>
            <w:r>
                <w:rPr><w:b/><w:sz w:val="22"/><w:color w:val="0F172A"/></w:rPr>
                <w:t>PAGE 1: STUDENT INFORMATION</w:t>
            </w:r>
        </w:p>

        <w:tbl>
            <w:tblPr>
                <w:tblW w:w="5000" w:type="pct"/>
                <w:tblBorders>
                    <w:top w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:left w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:bottom w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:right w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:insideH w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/>
                    <w:insideV w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/>
                </w:tblBorders>
            </w:tblPr>
            <w:tr>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>LRN:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $lrn . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>GRADE LEVEL:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $gradeLevel . '</w:t></w:r></w:p></w:tc>
            </w:tr>
            <w:tr>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>LAST NAME:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $lastName . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>FIRST NAME:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $firstName . '</w:t></w:r></w:p></w:tc>
            </w:tr>
            <w:tr>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>MIDDLE NAME:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $middleName . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>SEX:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $sex . '</w:t></w:r></w:p></w:tc>
            </w:tr>
            <w:tr>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>AGE:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $age . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>DATE OF BIRTH:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $dob . '</w:t></w:r></w:p></w:tc>
            </w:tr>
            <w:tr>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>PLACE OF BIRTH:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $pob . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>RELIGION:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $religion . '</w:t></w:r></w:p></w:tc>
            </w:tr>
            <w:tr>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>ADDRESS:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $address . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>TELEPHONE NO.:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $phone . '</w:t></w:r></w:p></w:tc>
            </w:tr>
            <w:tr>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>PREVIOUS SCHOOL:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $prevSchool . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>PREVIOUS SCHOOL ADDRESS:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $prevSchoolAddr . '</w:t></w:r></w:p></w:tc>
            </w:tr>
        </w:tbl>

        <w:p/>

        <!-- Section 2: Parent Information -->
        <w:p>
            <w:r>
                <w:rPr><w:b/><w:sz w:val="22"/><w:color w:val="0F172A"/></w:rPr>
                <w:t>PARENT INFORMATION</w:t>
            </w:r>
        </w:p>

        <w:tbl>
            <w:tblPr>
                <w:tblW w:w="5000" w:type="pct"/>
                <w:tblBorders>
                    <w:top w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:left w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:bottom w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:right w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:insideH w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/>
                    <w:insideV w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/>
                </w:tblBorders>
            </w:tblPr>
            <w:tr>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>FATHER\'S FULL NAME:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $fatherName . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>OCCUPATION:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $fatherOcc . '</w:t></w:r></w:p></w:tc>
            </w:tr>
            <w:tr>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>FATHER TEL / EMAIL:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $fatherContact . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>-</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>-</w:t></w:r></w:p></w:tc>
            </w:tr>
            <w:tr>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>MOTHER\'S FULL NAME:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $motherName . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>OCCUPATION:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $motherOcc . '</w:t></w:r></w:p></w:tc>
            </w:tr>
            <w:tr>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>MOTHER TEL / EMAIL:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $motherContact . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>HOME ADDRESS:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $homeAddress . '</w:t></w:r></w:p></w:tc>
            </w:tr>
        </w:tbl>

        <!-- Page 2 Break -->
        <w:p><w:r><w:br w:type="page"/></w:r></w:p>

        <!-- PAGE 2: Medical, Emergency Contacts, Referral & Signatures -->
        <w:p>
            <w:r>
                <w:rPr><w:b/><w:sz w:val="22"/><w:color w:val="0F172A"/></w:rPr>
                <w:t>PAGE 2: MEDICAL INFORMATION &amp; EMERGENCY CONTACTS</w:t>
            </w:r>
        </w:p>

        <w:tbl>
            <w:tblPr>
                <w:tblW w:w="5000" w:type="pct"/>
                <w:tblBorders>
                    <w:top w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:left w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:bottom w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:right w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/>
                    <w:insideH w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/>
                    <w:insideV w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/>
                </w:tblBorders>
            </w:tblPr>
            <w:tr>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>PSYCHOLOGICAL TESTING:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $medExplain . '</w:t></w:r></w:p></w:tc>
            </w:tr>
            <w:tr>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>PRESCRIPTION MEDICATION:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $medMeds . '</w:t></w:r></w:p></w:tc>
            </w:tr>
            <w:tr>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>FAMILY PHYSICIAN:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $physician . ' (' . $physicianPhone . ')</w:t></w:r></w:p></w:tc>
            </w:tr>
            <w:tr>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>EMERGENCY CONTACT:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $emergName . ' - ' . $emergRel . ' (' . $emergPhone . ')</w:t></w:r></w:p></w:tc>
            </w:tr>
            <w:tr>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>REFERRAL SOURCE:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $referral . '</w:t></w:r></w:p></w:tc>
            </w:tr>
            <w:tr>
                <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>PARENT / GUARDIAN SIGNATURE:</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . $fatherName . ' / ' . $motherName . ' (Date: ' . $regDate . ')</w:t></w:r></w:p></w:tc>
            </w:tr>
        </w:tbl>
    </w:body>
</w:document>';
    }
}';
    }
}
