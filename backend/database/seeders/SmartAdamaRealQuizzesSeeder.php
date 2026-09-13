<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;

class SmartAdamaRealQuizzesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Generating REAL quizzes for Smart Adama chapters...');

        // Map of chapter order/keyword to real questions
        $quizData = [
            1 => [ // Introduction
                'title' => 'Introduction to Smart Adama',
                'questions' => [
                    ['text' => 'What is the primary goal of the Smart Adama initiative?', 'options' => [
                        ['text' => 'To transform Adama into a technology-driven, sustainable city.', 'correct' => true],
                        ['text' => 'To restrict urban population growth.', 'correct' => false],
                        ['text' => 'To focus solely on rural agricultural development.', 'correct' => false],
                        ['text' => 'To privatize all city services.', 'correct' => false],
                    ]],
                    ['text' => 'Which of the following is a core pillar of a Smart City?', 'options' => [
                        ['text' => 'Smart Infrastructure', 'correct' => true],
                        ['text' => 'Increased fossil fuel usage', 'correct' => false],
                        ['text' => 'Manual record keeping', 'correct' => false],
                        ['text' => 'Isolation from global markets', 'correct' => false],
                    ]],
                    ['text' => 'How does citizen engagement factor into the Smart Adama framework?', 'options' => [
                        ['text' => 'It is central to ensuring services meet real community needs.', 'correct' => true],
                        ['text' => 'It is discouraged to speed up decision making.', 'correct' => false],
                        ['text' => 'It is only relevant during elections.', 'correct' => false],
                        ['text' => 'It is handled entirely by automated bots.', 'correct' => false],
                    ]],
                    ['text' => 'What role does data play in a Smart City?', 'options' => [
                        ['text' => 'Data drives informed decision-making and efficient resource allocation.', 'correct' => true],
                        ['text' => 'Data is only used for archiving past events.', 'correct' => false],
                        ['text' => 'Data is considered unreliable compared to intuition.', 'correct' => false],
                        ['text' => 'Data is primarily used to slow down administrative processes.', 'correct' => false],
                    ]],
                    ['text' => 'Which technology is foundational to Smart City connectivity?', 'options' => [
                        ['text' => 'Internet of Things (IoT)', 'correct' => true],
                        ['text' => 'Analog radio broadcasting', 'correct' => false],
                        ['text' => 'Typewriters', 'correct' => false],
                        ['text' => 'Pneumatic tubes', 'correct' => false],
                    ]],
                ]
            ],
            2 => [ // e-Governance
                'title' => 'e-Governance',
                'questions' => [
                    ['text' => 'What is e-Governance in the context of Smart Adama?', 'options' => [
                        ['text' => 'The application of ICT for delivering government services efficiently.', 'correct' => true],
                        ['text' => 'Governing the internet infrastructure only.', 'correct' => false],
                        ['text' => 'A system that replaces all human government workers with AI.', 'correct' => false],
                        ['text' => 'Electronic voting systems exclusively.', 'correct' => false],
                    ]],
                    ['text' => 'What is a major benefit of implementing e-Governance?', 'options' => [
                        ['text' => 'Increased transparency and reduced corruption.', 'correct' => true],
                        ['text' => 'Higher physical paperwork requirements.', 'correct' => false],
                        ['text' => 'Slower service delivery times.', 'correct' => false],
                        ['text' => 'Decreased citizen participation.', 'correct' => false],
                    ]],
                    ['text' => 'Which of the following describes a "One-Stop Service Portal"?', 'options' => [
                        ['text' => 'A single digital platform where citizens can access multiple government services.', 'correct' => true],
                        ['text' => 'A physical building for all government offices.', 'correct' => false],
                        ['text' => 'A website that only provides information without interaction.', 'correct' => false],
                        ['text' => 'A portal for government officials to communicate internally.', 'correct' => false],
                    ]],
                    ['text' => 'How does e-Governance improve service accessibility?', 'options' => [
                        ['text' => 'By allowing citizens to access services 24/7 from anywhere.', 'correct' => true],
                        ['text' => 'By requiring citizens to visit offices during strict business hours.', 'correct' => false],
                        ['text' => 'By limiting services to those with advanced technical skills.', 'correct' => false],
                        ['text' => 'By making services available only via physical kiosks.', 'correct' => false],
                    ]],
                    ['text' => 'What is essential for securing e-Governance systems?', 'options' => [
                        ['text' => 'Robust cybersecurity frameworks and data privacy policies.', 'correct' => true],
                        ['text' => 'Keeping servers in undisclosed locations.', 'correct' => false],
                        ['text' => 'Using open, unencrypted networks for all transactions.', 'correct' => false],
                        ['text' => 'Relying solely on paper backups.', 'correct' => false],
                    ]],
                ]
            ],
            3 => [ // Innovation
                'title' => 'Innovation',
                'questions' => [
                    ['text' => 'Why is innovation crucial for Smart Adama?', 'options' => [
                        ['text' => 'It drives continuous improvement and adaptation to new urban challenges.', 'correct' => true],
                        ['text' => 'It ensures the city remains traditional.', 'correct' => false],
                        ['text' => 'It is a requirement for international funding only.', 'correct' => false],
                        ['text' => 'It replaces the need for basic infrastructure.', 'correct' => false],
                    ]],
                    ['text' => 'What is an "Innovation Hub" in a smart city context?', 'options' => [
                        ['text' => 'A collaborative space for startups, researchers, and tech enthusiasts to develop solutions.', 'correct' => true],
                        ['text' => 'A central government office for issuing patents.', 'correct' => false],
                        ['text' => 'A factory that mass-produces electronic devices.', 'correct' => false],
                        ['text' => 'A museum showcasing past inventions.', 'correct' => false],
                    ]],
                    ['text' => 'How can a city foster a culture of innovation?', 'options' => [
                        ['text' => 'By supporting education, providing funding for startups, and encouraging open data.', 'correct' => true],
                        ['text' => 'By heavily regulating new technologies.', 'correct' => false],
                        ['text' => 'By restricting access to city data.', 'correct' => false],
                        ['text' => 'By relying solely on imported solutions.', 'correct' => false],
                    ]],
                    ['text' => 'What is the role of open data in driving innovation?', 'options' => [
                        ['text' => 'It allows developers to create new apps and services that solve city problems.', 'correct' => true],
                        ['text' => 'It compromises citizen privacy.', 'correct' => false],
                        ['text' => 'It is only useful for government internal audits.', 'correct' => false],
                        ['text' => 'It reduces the need for private sector involvement.', 'correct' => false],
                    ]],
                    ['text' => 'Which emerging technology is most often associated with urban innovation?', 'options' => [
                        ['text' => 'Artificial Intelligence (AI)', 'correct' => true],
                        ['text' => 'Steam power', 'correct' => false],
                        ['text' => 'Telegraphy', 'correct' => false],
                        ['text' => 'Microfiche', 'correct' => false],
                    ]],
                ]
            ],
            4 => [ // Enterprise
                'title' => 'Enterprise',
                'questions' => [
                    ['text' => 'How does a Smart City support local enterprises?', 'options' => [
                        ['text' => 'By providing digital infrastructure and streamlining business registration.', 'correct' => true],
                        ['text' => 'By increasing bureaucratic hurdles.', 'correct' => false],
                        ['text' => 'By limiting market access to foreign companies only.', 'correct' => false],
                        ['text' => 'By taking ownership of local businesses.', 'correct' => false],
                    ]],
                    ['text' => 'What is the "Digital Economy" in the context of Smart Adama?', 'options' => [
                        ['text' => 'Economic activity that results from billions of online connections among people, businesses, and data.', 'correct' => true],
                        ['text' => 'An economy based entirely on cryptocurrency.', 'correct' => false],
                        ['text' => 'The manufacturing of digital hardware components.', 'correct' => false],
                        ['text' => 'An economy that excludes traditional brick-and-mortar stores.', 'correct' => false],
                    ]],
                    ['text' => 'Why is supporting SMEs (Small and Medium Enterprises) important for a smart city?', 'options' => [
                        ['text' => 'They are major drivers of job creation and local economic resilience.', 'correct' => true],
                        ['text' => 'They are easier to tax than large corporations.', 'correct' => false],
                        ['text' => 'They require less infrastructure support.', 'correct' => false],
                        ['text' => 'They are the primary source of technological innovation.', 'correct' => false],
                    ]],
                    ['text' => 'What characterizes a "Smart Enterprise"?', 'options' => [
                        ['text' => 'The use of data analytics and automation to optimize operations.', 'correct' => true],
                        ['text' => 'A business that only operates online.', 'correct' => false],
                        ['text' => 'An enterprise owned by the city government.', 'correct' => false],
                        ['text' => 'A business that refuses to use traditional marketing.', 'correct' => false],
                    ]],
                    ['text' => 'How can digital platforms benefit local enterprises?', 'options' => [
                        ['text' => 'By expanding their market reach beyond physical boundaries.', 'correct' => true],
                        ['text' => 'By increasing their physical footprint.', 'correct' => false],
                        ['text' => 'By isolating them from global competition.', 'correct' => false],
                        ['text' => 'By reducing the need for customer service.', 'correct' => false],
                    ]],
                ]
            ],
            5 => [ // Food Security
                'title' => 'Food Security',
                'questions' => [
                    ['text' => 'What does "Smart Food Security" involve in an urban setting?', 'options' => [
                        ['text' => 'Using technology to optimize food production, supply chains, and reduce waste.', 'correct' => true],
                        ['text' => 'Banning the import of all agricultural products.', 'correct' => false],
                        ['text' => 'Relying entirely on traditional farming methods.', 'correct' => false],
                        ['text' => 'Focusing only on food storage.', 'correct' => false],
                    ]],
                    ['text' => 'How can IoT sensors aid in urban agriculture?', 'options' => [
                        ['text' => 'By monitoring soil moisture and nutrient levels in real-time.', 'correct' => true],
                        ['text' => 'By harvesting crops automatically without human intervention.', 'correct' => false],
                        ['text' => 'By predicting market prices for crops.', 'correct' => false],
                        ['text' => 'By preventing all types of plant diseases.', 'correct' => false],
                    ]],
                    ['text' => 'What is precision agriculture?', 'options' => [
                        ['text' => 'Farming management based on observing, measuring, and responding to crop variability.', 'correct' => true],
                        ['text' => 'Farming only highly specific, exotic crops.', 'correct' => false],
                        ['text' => 'Farming using exact, unchangeable schedules regardless of weather.', 'correct' => false],
                        ['text' => 'Farming within enclosed laboratories only.', 'correct' => false],
                    ]],
                    ['text' => 'How does a smart supply chain improve food security?', 'options' => [
                        ['text' => 'By increasing transparency and reducing spoilage during transport.', 'correct' => true],
                        ['text' => 'By increasing the time food spends in transit.', 'correct' => false],
                        ['text' => 'By hiding the origin of food products.', 'correct' => false],
                        ['text' => 'By relying solely on manual tracking methods.', 'correct' => false],
                    ]],
                    ['text' => 'What role does data analytics play in food waste reduction?', 'options' => [
                        ['text' => 'It helps predict demand accurately, reducing overproduction and overstocking.', 'correct' => true],
                        ['text' => 'It encourages supermarkets to throw away food sooner.', 'correct' => false],
                        ['text' => 'It has no significant impact on food waste.', 'correct' => false],
                        ['text' => 'It is only used to calculate the cost of wasted food.', 'correct' => false],
                    ]],
                ]
            ],
            6 => [ // Digital Infrastructure
                'title' => 'Digital Infrastructure',
                'questions' => [
                    ['text' => 'What constitutes the backbone of a Smart City?', 'options' => [
                        ['text' => 'Robust, high-speed digital infrastructure like fiber optics and 5G.', 'correct' => true],
                        ['text' => 'Traditional road networks.', 'correct' => false],
                        ['text' => 'Physical government buildings.', 'correct' => false],
                        ['text' => 'Public parks and recreation areas.', 'correct' => false],
                    ]],
                    ['text' => 'What is a key characteristic of smart digital infrastructure?', 'options' => [
                        ['text' => 'Scalability and resilience.', 'correct' => true],
                        ['text' => 'Static capacity and inflexibility.', 'correct' => false],
                        ['text' => 'Vulnerability to single points of failure.', 'correct' => false],
                        ['text' => 'Exclusivity to government use.', 'correct' => false],
                    ]],
                    ['text' => 'Why are data centers critical for Smart Adama?', 'options' => [
                        ['text' => 'They store, process, and manage the massive amounts of data generated by city services.', 'correct' => true],
                        ['text' => 'They provide physical office space for IT workers.', 'correct' => false],
                        ['text' => 'They generate electricity for the city grid.', 'correct' => false],
                        ['text' => 'They are primarily used for archiving old, unused records.', 'correct' => false],
                    ]],
                    ['text' => 'What is the role of cloud computing in digital infrastructure?', 'options' => [
                        ['text' => 'It offers flexible, on-demand computing resources and data storage.', 'correct' => true],
                        ['text' => 'It replaces the need for physical networks.', 'correct' => false],
                        ['text' => 'It is a weather forecasting tool.', 'correct' => false],
                        ['text' => 'It restricts data access to local servers only.', 'correct' => false],
                    ]],
                    ['text' => 'How does cybersecurity relate to digital infrastructure?', 'options' => [
                        ['text' => 'It is essential for protecting infrastructure from digital threats and ensuring service continuity.', 'correct' => true],
                        ['text' => 'It is an optional add-on for non-critical systems.', 'correct' => false],
                        ['text' => 'It only focuses on protecting individual citizen\'s personal computers.', 'correct' => false],
                        ['text' => 'It slows down infrastructure performance without significant benefits.', 'correct' => false],
                    ]],
                ]
            ],
            7 => [ // Smart Mobility
                'title' => 'Smart Mobility',
                'questions' => [
                    ['text' => 'What is the primary objective of Smart Mobility?', 'options' => [
                        ['text' => 'To provide efficient, safe, and sustainable transportation options.', 'correct' => true],
                        ['text' => 'To maximize the number of private cars on the road.', 'correct' => false],
                        ['text' => 'To eliminate all forms of public transit.', 'correct' => false],
                        ['text' => 'To increase travel times through complex routing.', 'correct' => false],
                    ]],
                    ['text' => 'How do Intelligent Transportation Systems (ITS) improve traffic flow?', 'options' => [
                        ['text' => 'By using real-time data to adjust traffic signals and inform drivers of congestion.', 'correct' => true],
                        ['text' => 'By setting traffic lights to a fixed, unchanging schedule.', 'correct' => false],
                        ['text' => 'By building more roads without analyzing traffic patterns.', 'correct' => false],
                        ['text' => 'By relying solely on traffic police to direct cars.', 'correct' => false],
                    ]],
                    ['text' => 'What role do electric vehicles (EVs) play in Smart Mobility?', 'options' => [
                        ['text' => 'They reduce urban air pollution and reliance on fossil fuels.', 'correct' => true],
                        ['text' => 'They increase noise pollution in city centers.', 'correct' => false],
                        ['text' => 'They are less efficient than traditional combustion engines.', 'correct' => false],
                        ['text' => 'They require no infrastructure changes to support.', 'correct' => false],
                    ]],
                    ['text' => 'What is "Mobility as a Service" (MaaS)?', 'options' => [
                        ['text' => 'Integrating various forms of transport into a single accessible on-demand service.', 'correct' => true],
                        ['text' => 'A government department responsible for road maintenance.', 'correct' => false],
                        ['text' => 'The practice of owning multiple personal vehicles.', 'correct' => false],
                        ['text' => 'A traditional taxi service.', 'correct' => false],
                    ]],
                    ['text' => 'How can smart parking systems benefit a city?', 'options' => [
                        ['text' => 'By reducing the time drivers spend looking for parking, thereby decreasing congestion and emissions.', 'correct' => true],
                        ['text' => 'By increasing the cost of parking universally.', 'correct' => false],
                        ['text' => 'By eliminating the need for parking spaces entirely.', 'correct' => false],
                        ['text' => 'By reserving parking only for government officials.', 'correct' => false],
                    ]],
                ]
            ],
            8 => [ // Smart Health
                'title' => 'Smart Health',
                'questions' => [
                    ['text' => 'What is a core component of a Smart Health system?', 'options' => [
                        ['text' => 'Electronic Health Records (EHR) accessible across healthcare providers.', 'correct' => true],
                        ['text' => 'Paper-based patient files stored in a central warehouse.', 'correct' => false],
                        ['text' => 'Healthcare services provided exclusively in large hospitals.', 'correct' => false],
                        ['text' => 'Restricting patient access to their own medical data.', 'correct' => false],
                    ]],
                    ['text' => 'How does telemedicine contribute to Smart Health?', 'options' => [
                        ['text' => 'It allows remote diagnosis and treatment, increasing access to care.', 'correct' => true],
                        ['text' => 'It replaces the need for doctors entirely.', 'correct' => false],
                        ['text' => 'It is only used for administrative tasks in hospitals.', 'correct' => false],
                        ['text' => 'It requires patients to travel further for consultations.', 'correct' => false],
                    ]],
                    ['text' => 'What role do wearable devices play in urban health?', 'options' => [
                        ['text' => 'They enable continuous monitoring of patient vitals and proactive health management.', 'correct' => true],
                        ['text' => 'They are primarily fashion accessories with no medical value.', 'correct' => false],
                        ['text' => 'They replace the need for regular medical checkups.', 'correct' => false],
                        ['text' => 'They are only useful for professional athletes.', 'correct' => false],
                    ]],
                    ['text' => 'How can data analytics improve public health outcomes?', 'options' => [
                        ['text' => 'By identifying disease outbreak patterns and optimizing resource allocation.', 'correct' => true],
                        ['text' => 'By creating personalized marketing for pharmaceutical companies.', 'correct' => false],
                        ['text' => 'By increasing the cost of healthcare services.', 'correct' => false],
                        ['text' => 'By hiding public health statistics from citizens.', 'correct' => false],
                    ]],
                    ['text' => 'What is the benefit of a connected ambulance?', 'options' => [
                        ['text' => 'It can transmit patient data to the hospital en route, allowing for immediate treatment upon arrival.', 'correct' => true],
                        ['text' => 'It travels faster than a regular ambulance.', 'correct' => false],
                        ['text' => 'It requires fewer medical personnel on board.', 'correct' => false],
                        ['text' => 'It is immune to traffic congestion.', 'correct' => false],
                    ]],
                ]
            ],
            9 => [ // Smart Education
                'title' => 'Smart Education',
                'questions' => [
                    ['text' => 'What characterizes a Smart Education environment?', 'options' => [
                        ['text' => 'The integration of digital tools and personalized learning platforms.', 'correct' => true],
                        ['text' => 'Strict reliance on traditional textbooks and rote memorization.', 'correct' => false],
                        ['text' => 'The elimination of all physical classrooms.', 'correct' => false],
                        ['text' => 'Standardized testing as the sole measure of success.', 'correct' => false],
                    ]],
                    ['text' => 'How do Learning Management Systems (LMS) benefit students?', 'options' => [
                        ['text' => 'They provide centralized access to course materials, assignments, and feedback.', 'correct' => true],
                        ['text' => 'They monitor student behavior outside of school hours.', 'correct' => false],
                        ['text' => 'They automatically generate grades without teacher input.', 'correct' => false],
                        ['text' => 'They restrict communication between students and teachers.', 'correct' => false],
                    ]],
                    ['text' => 'What is the role of AI in personalized learning?', 'options' => [
                        ['text' => 'It adapts content and pacing to fit individual student needs and learning styles.', 'correct' => true],
                        ['text' => 'It replaces the teacher entirely.', 'correct' => false],
                        ['text' => 'It enforces a rigid curriculum for all students.', 'correct' => false],
                        ['text' => 'It is only used for grading multiple-choice exams.', 'correct' => false],
                    ]],
                    ['text' => 'Why is digital literacy important in Smart Adama?', 'options' => [
                        ['text' => 'It equips citizens with the skills needed to navigate and succeed in a digital economy.', 'correct' => true],
                        ['text' => 'It is only necessary for IT professionals.', 'correct' => false],
                        ['text' => 'It reduces the need for traditional reading and writing skills.', 'correct' => false],
                        ['text' => 'It is a passing trend that will soon be obsolete.', 'correct' => false],
                    ]],
                    ['text' => 'How can virtual reality (VR) enhance education?', 'options' => [
                        ['text' => 'By providing immersive, interactive simulations of complex concepts or historical events.', 'correct' => true],
                        ['text' => 'By isolating students from their peers.', 'correct' => false],
                        ['text' => 'By replacing all physical lab experiments.', 'correct' => false],
                        ['text' => 'By making learning less engaging.', 'correct' => false],
                    ]],
                ]
            ],
            10 => [ // Smart Environment
                'title' => 'Smart Environment',
                'questions' => [
                    ['text' => 'What is the primary goal of environmental monitoring in a Smart City?', 'options' => [
                        ['text' => 'To track air and water quality in real-time and mitigate pollution.', 'correct' => true],
                        ['text' => 'To control the weather.', 'correct' => false],
                        ['text' => 'To increase industrial emissions.', 'correct' => false],
                        ['text' => 'To monitor citizen movement in parks.', 'correct' => false],
                    ]],
                    ['text' => 'How do smart grids contribute to a sustainable environment?', 'options' => [
                        ['text' => 'By optimizing energy distribution and integrating renewable energy sources efficiently.', 'correct' => true],
                        ['text' => 'By increasing reliance on coal power plants.', 'correct' => false],
                        ['text' => 'By causing frequent power outages.', 'correct' => false],
                        ['text' => 'By standardizing energy costs regardless of usage time.', 'correct' => false],
                    ]],
                    ['text' => 'What is a "Smart Waste Management" system?', 'options' => [
                        ['text' => 'Using sensors in bins to optimize collection routes based on fill levels.', 'correct' => true],
                        ['text' => 'Banning all forms of waste disposal.', 'correct' => false],
                        ['text' => 'Incinerating all trash regardless of material.', 'correct' => false],
                        ['text' => 'Paying citizens to keep trash in their homes.', 'correct' => false],
                    ]],
                    ['text' => 'How can a city encourage energy efficiency?', 'options' => [
                        ['text' => 'By promoting the use of smart meters and energy-efficient building standards.', 'correct' => true],
                        ['text' => 'By subsidizing inefficient appliances.', 'correct' => false],
                        ['text' => 'By leaving public lighting on 24/7.', 'correct' => false],
                        ['text' => 'By restricting access to renewable energy technology.', 'correct' => false],
                    ]],
                    ['text' => 'What role do urban green spaces play in a Smart Environment?', 'options' => [
                        ['text' => 'They improve air quality, reduce the urban heat island effect, and enhance citizen well-being.', 'correct' => true],
                        ['text' => 'They are primarily reserved for commercial development.', 'correct' => false],
                        ['text' => 'They increase urban pollution.', 'correct' => false],
                        ['text' => 'They require excessive amounts of water to maintain.', 'correct' => false],
                    ]],
                ]
            ],
            11 => [ // Smart People
                'title' => 'Smart People',
                'questions' => [
                    ['text' => 'What is meant by "Smart People" in the context of a Smart City?', 'options' => [
                        ['text' => 'Empowered, engaged citizens who actively participate in city life and governance.', 'correct' => true],
                        ['text' => 'Only citizens with advanced degrees in technology.', 'correct' => false],
                        ['text' => 'A population that passively accepts government decisions.', 'correct' => false],
                        ['text' => 'Citizens who do not use digital services.', 'correct' => false],
                    ]],
                    ['text' => 'How does digital inclusion benefit a Smart City?', 'options' => [
                        ['text' => 'It ensures all citizens, regardless of background, have access to digital services and opportunities.', 'correct' => true],
                        ['text' => 'It widens the gap between different socioeconomic groups.', 'correct' => false],
                        ['text' => 'It focuses resources only on the most tech-savvy individuals.', 'correct' => false],
                        ['text' => 'It decreases overall city productivity.', 'correct' => false],
                    ]],
                    ['text' => 'Why is continuous learning important for Smart People?', 'options' => [
                        ['text' => 'It allows citizens to adapt to rapid technological changes and new economic opportunities.', 'correct' => true],
                        ['text' => 'It is only necessary for children in school.', 'correct' => false],
                        ['text' => 'It ensures traditional skills are never updated.', 'correct' => false],
                        ['text' => 'It is a mandatory government requirement with penalties.', 'correct' => false],
                    ]],
                    ['text' => 'What characterizes a participatory culture in a smart city?', 'options' => [
                        ['text' => 'Citizens co-creating solutions and providing feedback on city services.', 'correct' => true],
                        ['text' => 'A top-down approach where citizens have no voice.', 'correct' => false],
                        ['text' => 'A focus solely on consumerism.', 'correct' => false],
                        ['text' => 'Citizens avoiding interaction with local government.', 'correct' => false],
                    ]],
                    ['text' => 'How can a city foster a sense of community among "Smart People"?', 'options' => [
                        ['text' => 'By providing platforms for civic engagement, community projects, and open dialogue.', 'correct' => true],
                        ['text' => 'By restricting public gatherings.', 'correct' => false],
                        ['text' => 'By encouraging isolation through technology.', 'correct' => false],
                        ['text' => 'By prioritizing corporate interests over community needs.', 'correct' => false],
                    ]],
                ]
            ]
        ];

        DB::transaction(function () use ($quizData) {
            // Truncate existing quizzes
            DB::statement('TRUNCATE TABLE quiz_options CASCADE');
            DB::statement('TRUNCATE TABLE quiz_questions CASCADE');
            DB::statement('TRUNCATE TABLE quizzes CASCADE');

            // Get up to 11 chapters ordered by their sequence
            $chapters = Chapter::orderBy('order')->limit(11)->get();

            foreach ($chapters as $index => $chapter) {
                $realChapterNum = $index + 1;
                $data = $quizData[$realChapterNum] ?? null;

                if (!$data) continue;

                // Create Quiz
                $quiz = Quiz::create([
                    'chapter_id' => $chapter->id,
                    'title' => 'Quiz: ' . $chapter->title,
                    'passing_score_pct' => 70,
                    'status' => 'published',
                ]);

                // Generate exactly 5 questions
                foreach ($data['questions'] as $qIndex => $qData) {
                    $question = QuizQuestion::create([
                        'quiz_id' => $quiz->id,
                        'order' => $qIndex + 1,
                        'question_text' => $qData['text'],
                        'type' => 'single',
                        'explanation' => 'Detailed explanation of the concept based on the Smart Adama framework.'
                    ]);

                    foreach ($qData['options'] as $optIndex => $optData) {
                        $question->options()->create([
                            'option_text' => $optData['text'],
                            'order' => $optIndex + 1,
                            'is_correct' => $optData['correct'],
                        ]);
                    }
                }
            }
        });

        $this->command->info('✅ Successfully populated actual Smart Adama quizzes for all 11 chapters!');
    }
}
