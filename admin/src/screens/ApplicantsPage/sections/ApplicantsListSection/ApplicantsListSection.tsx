import {
  CalendarIcon,
  ChevronDownIcon,
  MapPinIcon,
  SearchIcon,
  XIcon,
} from "lucide-react";
import { Badge } from "../../../../components/ui/badge";
import { Button } from "../../../../components/ui/button";
import { Card, CardContent } from "../../../../components/ui/card";
import { Checkbox } from "../../../../components/ui/checkbox";
import { Input } from "../../../../components/ui/input";
import { Separator } from "../../../../components/ui/separator";

const specialities = [
  { name: "Household Management", count: 21 },
  { name: "Nanny/Yaya", count: 21 },
  { name: "Gardening", count: 21 },
  { name: "Kitchen and Cook", count: 21 },
  { name: "Home Support", count: 21 },
  { name: "Elderly Care", count: 21 },
  { name: "Pet Care", count: 21 },
];

const availabilityOptions = [
  "Available Today",
  "Available Tomorrow",
  "Available in Next 7 Days",
  "Available in Next 30 Days",
];

const experienceOptions = ["First Timer", "2+ Years", "5+ Years"];

const languageOptions = ["English", "Filipino"];

const applicants = [
  {
    name: "Miranda Soliman",
    specialty: "Kitchen and Cook",
    specialtyColor: "border-[#0e9384]",
    specialtyTextColor: "text-[#0e9384]",
    location: "Cavite",
    employmentType: "Full Time",
    buttonColor: "bg-[#ff4f4f]",
    image: "",
  },
  {
    name: "Alicia Tabon",
    specialty: "Gardening",
    specialtyColor: "border-[#110efd]",
    specialtyTextColor: "text-[#110efd]",
    location: "Manila",
    employmentType: "Part Time",
    buttonColor: "bg-[#ff4f4f]",
    image: "",
  },
  {
    name: "Malaiah Kanah",
    specialty: "Nanny/Yaya",
    specialtyColor: "border-[#110efd]",
    specialtyTextColor: "text-[#3538cd]",
    location: "Caloocan",
    employmentType: "Full Time",
    buttonColor: "bg-[#ff4f4f]",
    image: "",
  },
  {
    name: "Altina Moran",
    specialty: "Pet Care",
    specialtyColor: "border-[#dd2590]",
    specialtyTextColor: "text-[#dd2590]",
    location: "Quezon City",
    employmentType: "Part Time",
    buttonColor: "bg-[#000f28]",
    image: "",
  },
  {
    name: "Anabelle Latosa",
    specialty: "Nanny/Yaya",
    specialtyColor: "border-[#3538cd]",
    specialtyTextColor: "text-[#3538cd]",
    location: "Antipolo",
    employmentType: "Full Time",
    buttonColor: "bg-[#000f28]",
    image: "",
  },
  {
    name: "Marites Dimagiba",
    specialty: "Pet Care",
    specialtyColor: "border-[#dd2590]",
    specialtyTextColor: "text-[#dd2590]",
    location: "Pasay",
    employmentType: "Full Time",
    buttonColor: "bg-[#000f28]",
    image: "",
  },
  {
    name: "Anne Bajo",
    specialty: "Gardening",
    specialtyColor: "border-[#110efd]",
    specialtyTextColor: "text-[#110efd]",
    location: "San Juan",
    employmentType: "Part Time",
    buttonColor: "bg-[#000f28]",
    image: "",
  },
  {
    name: "Dina Macuja",
    specialty: "Kitchen and Cook",
    specialtyColor: "border-[#0e9384]",
    specialtyTextColor: "text-[#0e9384]",
    location: "Mandaluyong",
    employmentType: "Part Time",
    buttonColor: "bg-[#000f28]",
    image: "",
  },
  {
    name: "Eva Manini",
    specialty: "Gardening",
    specialtyColor: "border-[#110efd]",
    specialtyTextColor: "text-[#110efd]",
    location: "Pasig",
    employmentType: "Full Time",
    buttonColor: "bg-[#000f28]",
    image: "",
  },
  {
    name: "Molly Fernandez",
    specialty: "Nanny/Yaya",
    specialtyColor: "border-[#3538cd]",
    specialtyTextColor: "text-[#3538cd]",
    location: "Manila",
    employmentType: "Full Time",
    buttonColor: "bg-[#000f28]",
    image: "",
  },
  {
    name: "Juana Dela Cruz",
    specialty: "Elderly Care",
    specialtyColor: "border-[#ff0000]",
    specialtyTextColor: "text-[#ff0000]",
    location: "Bacolod",
    employmentType: "Full Time",
    buttonColor: "bg-[#000f28]",
    distance: "60 Min",
    image: "",
  },
  {
    name: "Nadia Cole Bolivar",
    specialty: "Pet Care",
    specialtyColor: "border-[#dd2590]",
    specialtyTextColor: "text-[#dd2590]",
    location: "Bulacan",
    employmentType: "Part Time",
    buttonColor: "bg-[#000f28]",
    image: "",
  },
];

const paginationNumbers = [1, 2, 3, 4, 5];

export const ApplicantsListSection = (): JSX.Element => {
  return (
    <section className="flex items-start gap-6 w-full max-w-[1300px] mx-auto px-4">
      <Card className="w-[309px] flex-shrink-0 bg-white rounded-[10px] border border-[#e6e8ee] shadow-shadow-default">
        <CardContent className="p-0">
          <div className="flex items-center justify-between px-5 py-[15px] bg-[#f9fbff] rounded-t-[10px]">
            <h2 className="font-heading-heading-4 font-[number:var(--heading-heading-4-font-weight)] text-[#012047] text-[length:var(--heading-heading-4-font-size)] tracking-[var(--heading-heading-4-letter-spacing)] leading-[var(--heading-heading-4-line-height)] [font-style:var(--heading-heading-4-font-style)]">
              Filter
            </h2>
            <button className="[font-family:'Inter',Helvetica] font-medium text-[#822bd4] text-sm underline">
              Clear All
            </button>
          </div>

          <div className="flex flex-col">
            <div className="flex flex-col gap-2.5 p-5 border-b border-[#e6e8ee]">
              <div className="relative">
                <SearchIcon className="absolute left-4 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-[#91a0b3]" />
                <Input
                  placeholder="Search"
                  className="w-full pl-10 pr-4 py-[7px] bg-white rounded-[5px] border border-[#e6e8ee] [font-family:'IBM_Plex_Sans',Helvetica] font-normal text-[#91a0b3] text-sm"
                />
              </div>
            </div>

            <div className="flex flex-col gap-4 p-5 border-b border-[#e6e8ee]">
              <div className="flex items-center justify-between">
                <h3 className="font-heading-heading-6 font-[number:var(--heading-heading-6-font-weight)] text-[#012047] text-[length:var(--heading-heading-6-font-size)] tracking-[var(--heading-heading-6-letter-spacing)] leading-[var(--heading-heading-6-line-height)] [font-style:var(--heading-heading-6-font-style)]">
                  Specialities
                </h3>
                <ChevronDownIcon className="w-4 h-4" />
              </div>
              <div className="flex flex-col gap-2">
                {specialities.map((item, index) => (
                  <div key={index} className="flex items-start gap-2">
                    <div className="flex items-center gap-2 flex-1">
                      <Checkbox className="w-4 h-4 rounded-sm border-[#e6e8ee]" />
                      <label className="font-body-large-body-lg-medium font-[number:var(--body-large-body-lg-medium-font-weight)] text-[#465d7c] text-[length:var(--body-large-body-lg-medium-font-size)] tracking-[var(--body-large-body-lg-medium-letter-spacing)] leading-[var(--body-large-body-lg-medium-line-height)] [font-style:var(--body-large-body-lg-medium-font-style)] cursor-pointer">
                        {item.name}
                      </label>
                    </div>
                    <Badge className="w-5 h-5 flex items-center justify-center bg-[#e3e6ec] rounded-full font-body-extra-small-body-XS-regular font-[number:var(--body-extra-small-body-XS-regular-font-weight)] text-[#012047] text-[length:var(--body-extra-small-body-XS-regular-font-size)] tracking-[var(--body-extra-small-body-XS-regular-letter-spacing)] leading-[var(--body-extra-small-body-XS-regular-line-height)] [font-style:var(--body-extra-small-body-XS-regular-font-style)] hover:bg-[#e3e6ec]">
                      {item.count}
                    </Badge>
                  </div>
                ))}
              </div>
            </div>

            <div className="flex flex-col gap-4 p-5 border-b border-[#e6e8ee]">
              <div className="flex items-center justify-between">
                <h3 className="font-heading-heading-6 font-[number:var(--heading-heading-6-font-weight)] text-[#012047] text-[length:var(--heading-heading-6-font-size)] tracking-[var(--heading-heading-6-letter-spacing)] leading-[var(--heading-heading-6-line-height)] [font-style:var(--heading-heading-6-font-style)]">
                  Availability
                </h3>
                <ChevronDownIcon className="w-4 h-4" />
              </div>
              <div className="flex flex-col gap-2">
                {availabilityOptions.map((option, index) => (
                  <div key={index} className="flex items-center gap-2">
                    <Checkbox className="w-4 h-4 rounded-sm border-[#e6e8ee]" />
                    <label className="font-body-large-body-lg-medium font-[number:var(--body-large-body-lg-medium-font-weight)] text-[#465d7c] text-[length:var(--body-large-body-lg-medium-font-size)] tracking-[var(--body-large-body-lg-medium-letter-spacing)] leading-[var(--body-large-body-lg-medium-line-height)] [font-style:var(--body-large-body-lg-medium-font-style)] cursor-pointer">
                      {option}
                    </label>
                  </div>
                ))}
              </div>
              <button className="[font-family:'Inter',Helvetica] font-medium text-[#822bd4] text-sm underline text-left">
                View More
              </button>
            </div>

            <div className="flex flex-col gap-4 p-5 border-b border-[#e6e8ee]">
              <div className="flex items-center justify-between">
                <h3 className="font-heading-heading-6 font-[number:var(--heading-heading-6-font-weight)] text-[#012047] text-[length:var(--heading-heading-6-font-size)] tracking-[var(--heading-heading-6-letter-spacing)] leading-[var(--heading-heading-6-line-height)] [font-style:var(--heading-heading-6-font-style)]">
                  Experience
                </h3>
                <ChevronDownIcon className="w-4 h-4" />
              </div>
              <div className="flex flex-col gap-2">
                {experienceOptions.map((option, index) => (
                  <div key={index} className="flex items-center gap-2">
                    <Checkbox className="w-4 h-4 rounded-sm border-[#e6e8ee]" />
                    <label className="font-body-large-body-lg-medium font-[number:var(--body-large-body-lg-medium-font-weight)] text-[#465d7c] text-[length:var(--body-large-body-lg-medium-font-size)] tracking-[var(--body-large-body-lg-medium-letter-spacing)] leading-[var(--body-large-body-lg-medium-line-height)] [font-style:var(--body-large-body-lg-medium-font-style)] cursor-pointer">
                      {option}
                    </label>
                  </div>
                ))}
              </div>
              <button className="[font-family:'Inter',Helvetica] font-medium text-[#822bd4] text-sm underline text-left">
                View More
              </button>
            </div>

            <div className="flex flex-col gap-4 p-5 border-b border-[#e6e8ee]">
              <div className="flex items-center justify-between">
                <h3 className="font-heading-heading-6 font-[number:var(--heading-heading-6-font-weight)] text-[#012047] text-[length:var(--heading-heading-6-font-size)] tracking-[var(--heading-heading-6-letter-spacing)] leading-[var(--heading-heading-6-line-height)] [font-style:var(--heading-heading-6-font-style)]">
                  Languages
                </h3>
                <ChevronDownIcon className="w-4 h-4" />
              </div>
              <div className="flex flex-col gap-2">
                {languageOptions.map((option, index) => (
                  <div key={index} className="flex items-center gap-2">
                    <Checkbox className="w-4 h-4 rounded-sm border-[#e6e8ee]" />
                    <label className="font-body-large-body-lg-medium font-[number:var(--body-large-body-lg-medium-font-weight)] text-[#465d7c] text-[length:var(--body-large-body-lg-medium-font-size)] tracking-[var(--body-large-body-lg-medium-letter-spacing)] leading-[var(--body-large-body-lg-medium-line-height)] [font-style:var(--body-large-body-lg-medium-font-style)] cursor-pointer">
                      {option}
                    </label>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <div className="flex flex-col flex-1 gap-10">
        <div className="flex items-center gap-6">
          <h1 className="font-heading-heading-5 font-[number:var(--heading-heading-5-font-weight)] text-[length:var(--heading-heading-5-font-size)] tracking-[var(--heading-heading-5-letter-spacing)] leading-[var(--heading-heading-5-line-height)] [font-style:var(--heading-heading-5-font-style)]">
            <span className="text-[#012047]">Showing </span>
            <span className="text-[#822bd4]">90</span>
            <span className="text-[#012047]"> Kasambahay For You</span>
          </h1>

          <div className="flex items-center justify-end gap-3 flex-1">
            <div className="flex items-end gap-2">
              <span className="font-body-large-body-lg-medium font-[number:var(--body-large-body-lg-medium-font-weight)] text-[#012047] text-[length:var(--body-large-body-lg-medium-font-size)] tracking-[var(--body-large-body-lg-medium-letter-spacing)] leading-[var(--body-large-body-lg-medium-line-height)] [font-style:var(--body-large-body-lg-medium-font-style)]">
                Availability
              </span>
              <div className="flex items-center gap-2 pl-0.5 pr-2 py-0.5 bg-[#e6e8ee] rounded-full">
                <div className="flex items-center gap-2.5 p-1 bg-white rounded-full shadow-shadow-default">
                  <XIcon className="w-3 h-3" />
                </div>
                <img
                  className="w-[13px] h-[13px]"
                  alt="Vector"
                  src="/vector.svg"
                />
              </div>
            </div>
            <Button
              variant="outline"
              size="icon"
              className="p-2 bg-white rounded-[5px] border border-[#e6e8ee]"
            >
              <img className="w-4 h-4" alt="Icon" src="/icon.svg" />
            </Button>
          </div>
        </div>

        <div className="flex flex-col gap-10">
          <div className="flex flex-col gap-6">
            <div className="grid grid-cols-3 gap-6">
              {applicants.map((applicant, index) => (
                <Card
                  key={index}
                  className="flex flex-col overflow-hidden bg-white rounded-[10px] border border-[#e6e8ee] shadow-shadow-default"
                >
                  <div className="w-full h-[195.37px] bg-gray-200">
                    <img
                      className="w-full h-full object-cover"
                      alt={applicant.name}
                      src={applicant.image}
                    />
                  </div>
                  <CardContent className="p-0">
                    <div className="flex flex-col gap-5 px-0 py-2">
                      <div
                        className={`flex items-center gap-5 px-5 py-1 border-l-2 ${applicant.specialtyColor}`}
                      >
                        <span
                          className={`flex-1 font-body-large-body-lg-medium font-[number:var(--body-large-body-lg-medium-font-weight)] text-[length:var(--body-large-body-lg-medium-font-size)] tracking-[var(--body-large-body-lg-medium-letter-spacing)] leading-[var(--body-large-body-lg-medium-line-height)] [font-style:var(--body-large-body-lg-medium-font-style)] ${applicant.specialtyTextColor}`}
                        >
                          {applicant.specialty}
                        </span>
                        <Badge className="flex items-center gap-[5px] p-2 bg-[#edf9f0] rounded hover:bg-[#edf9f0]">
                          <div className="w-[5px] h-[5px] bg-[#04bd6c] rounded-full" />
                          <span className="[font-family:'Inter',Helvetica] font-medium text-[#04bd6c] text-[10px] leading-[8px]">
                            Available
                          </span>
                        </Badge>
                      </div>
                    </div>

                    <div className="flex flex-col gap-4 px-5 pb-5">
                      <div className="flex flex-col gap-1">
                        <h3 className="font-heading-heading-6 font-[number:var(--heading-heading-6-font-weight)] text-[#012047] text-[length:var(--heading-heading-6-font-size)] tracking-[var(--heading-heading-6-letter-spacing)] leading-[var(--heading-heading-6-line-height)] [font-style:var(--heading-heading-6-font-style)]">
                          {applicant.name}
                        </h3>
                        <div className="flex items-center gap-2">
                          <MapPinIcon className="w-3.5 h-3.5 text-[#465d7c]" />
                          <span className="font-body-large-body-lg-regular font-[number:var(--body-large-body-lg-regular-font-weight)] text-[#465d7c] text-[length:var(--body-large-body-lg-regular-font-size)] tracking-[var(--body-large-body-lg-regular-letter-spacing)] leading-[var(--body-large-body-lg-regular-line-height)] [font-style:var(--body-large-body-lg-regular-font-style)]">
                            {applicant.location}
                          </span>
                          {applicant.distance && (
                            <>
                              <div className="w-[5.77px] h-[5.77px] bg-[#0e82fd] rounded-full" />
                              <span className="font-body-large-body-lg-medium font-[number:var(--body-large-body-lg-medium-font-weight)] text-[#465d7c] text-[length:var(--body-large-body-lg-medium-font-size)] tracking-[var(--body-large-body-lg-medium-letter-spacing)] leading-[var(--body-large-body-lg-medium-line-height)] [font-style:var(--body-large-body-lg-medium-font-style)]">
                                {applicant.distance}
                              </span>
                            </>
                          )}
                        </div>
                      </div>

                      <Separator className="bg-[#e6e8ee]" />

                      <div className="flex items-center gap-6">
                        <span className="flex-1 font-body-large-body-lg-regular font-[number:var(--body-large-body-lg-regular-font-weight)] text-[#465d7c] text-[length:var(--body-large-body-lg-regular-font-size)] tracking-[var(--body-large-body-lg-regular-letter-spacing)] leading-[var(--body-large-body-lg-regular-line-height)] [font-style:var(--body-large-body-lg-regular-font-style)]">
                          {applicant.employmentType}
                        </span>
                        <Button
                          className={`flex items-center gap-1 px-3 py-[7px] ${applicant.buttonColor} rounded-full hover:opacity-90`}
                        >
                          <CalendarIcon className="w-[13px] h-[13px] text-white" />
                          <span className="font-body-medium-body-md-regular font-[number:var(--body-medium-body-md-regular-font-weight)] text-white text-[length:var(--body-medium-body-md-regular-font-size)] tracking-[var(--body-medium-body-md-regular-letter-spacing)] leading-[var(--body-medium-body-md-regular-line-height)] [font-style:var(--body-medium-body-md-regular-font-style)]">
                            Hire Me
                          </span>
                        </Button>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>

          <div className="flex items-center justify-center gap-2">
            <Button
              variant="outline"
              className="h-[30px] px-4 py-0.5 bg-white rounded-full border border-[#e6e8ee] font-body-large-body-lg-regular font-[number:var(--body-large-body-lg-regular-font-weight)] text-[#012047] text-[length:var(--body-large-body-lg-regular-font-size)] tracking-[var(--body-large-body-lg-regular-letter-spacing)] leading-[var(--body-large-body-lg-regular-line-height)] [font-style:var(--body-large-body-lg-regular-font-style)]"
            >
              Prev
            </Button>

            <div className="flex items-center gap-[9px]">
              {paginationNumbers.map((num) => (
                <Button
                  key={num}
                  variant={num === 2 ? "default" : "outline"}
                  className={`w-[30px] h-[30px] p-0 rounded-full ${
                    num === 2
                      ? "bg-[#0e82fd] hover:bg-[#0e82fd]/90 text-white"
                      : "bg-white border border-[#e6e8ee] text-[#012047]"
                  } font-body-large-body-lg-regular font-[number:var(--body-large-body-lg-regular-font-weight)] text-[length:var(--body-large-body-lg-regular-font-size)] tracking-[var(--body-large-body-lg-regular-letter-spacing)] leading-[var(--body-large-body-lg-regular-line-height)] [font-style:var(--body-large-body-lg-regular-font-style)]`}
                >
                  {num}
                </Button>
              ))}
            </div>

            <Button
              variant="outline"
              className="h-[30px] px-4 py-[5px] bg-white rounded-full border border-[#e6e8ee] font-body-large-body-lg-regular font-[number:var(--body-large-body-lg-regular-font-weight)] text-[#012047] text-[length:var(--body-large-body-lg-regular-font-size)] tracking-[var(--body-large-body-lg-regular-letter-spacing)] leading-[var(--body-large-body-lg-regular-line-height)] [font-style:var(--body-large-body-lg-regular-font-style)]"
            >
              Next
            </Button>
          </div>
        </div>
      </div>
    </section>
  );
};
