## Chalmers
## Introduction
Report that provides an overview of the scheduled tasks and quizzes during the month and the user impact it will have on the platform.

## Tasks and quizzes of the month
This first graph gives an overview of the quizzes and tasks scheduled during the next 30 days.

## User impact
The second graph shows the user impact of the quizzes and tasks scheduled for the day in 30-minute intervals. By default, when you enter the report, it shows the impact of users on the current day.

### How do you calculate the users?
* This user count only takes into consideration the students of the course, i.e. users with a student role. 
* It does not take into account course groups, so if the quiz or assignment is only for one group within the course, the report will count all students.
* If several assignments or quizzes of the same course coincide in the same time interval, the report only counts the students of the course once.

**Example**, we have 5 quizzes in the course (one for each course group, 15 students per group) and they will all be done between 10:00 and 10:15. The report will count 75 students and not 375.

This chart provides a form where one can select the day to be displayed.

## Table - tasks and quizzes
This is a downloadable table containing the information of all the tasks and quizzes of the platform for the next 30 days.

### What information does it contain?
* Quiz/task: these columns have a direct link to the activity.
* Full name of the course: contains a direct link to the course where the activity belongs to.
* Teachers: lists the emails of all the users with the role of teacher.
* Start/end date: dates on which the activity is scheduled to start and end.
* Number of students: count of students attending the course and therefore involved in the activity.
* Time limit: estimated duration of the quizzes as long as it is set in the quiz configuration.
	
## Configuration
This report has a configurable alert system, these are the options:
* Number of user: the total number of users in an interval from which an alert is required.
* Alert days: margin of days to receive the alert regarding the start of the task/quiz.
* Reminder days: margin days to receive the second alert in case the peak number of users continues to occur.

The reminder can be deactivated by entering a 0 in its configuration field.

## Alerts
These are notifications sent to the platform's Managers and administrators showing possible user peaks resulting from quizzes and/or simultaneous tasks.

### What information does it give?
* On one side the alert indicates the day, time and number of users of the detected peak.
* Underneath, it will show a breakdown of the activities that make up the peak along with their link, the link to their course and the number of users that contribute to the peak.
* At the end of the message (in case the reminder is active) the section indicating peaks that have not been solved will be shown.

